<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Models\Utility\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BusinessTermsController extends Controller
{
    public function get(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $companyId = (int) ($request->query('company_id') ?? 0);
        if ($companyId <= 0) {
            $companyId = (int) ($user->company_id ?? 0);
        }

        if ($companyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No business selected for Terms & Conditions.',
            ], 422);
        }

        if (!$this->canAccessCompany($user, $companyId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $business = Business::query()->find($companyId);
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Business not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'company_id' => (int) $business->id,
                'dine_in_policies' => (string) ($business->dine_in_policies ?? ''),
                'delivery_terms' => (string) ($business->delivery_terms ?? ''),
                'created_at' => $business->created_at,
                'updated_at' => $business->updated_at,
                'created_by' => (string) ($business->created_by ?? ''),
                'lastmodified_by' => (string) ($business->lastmodified_by ?? ''),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $companyId = (int) ($request->input('company_id') ?? 0);
        if ($companyId <= 0) {
            $companyId = (int) ($user->company_id ?? 0);
        }

        if ($companyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No business selected for Terms & Conditions.',
            ], 422);
        }

        if (!$this->canAccessCompany($user, $companyId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'dine_in_policies' => 'nullable|string|max:500',
            'delivery_terms' => 'nullable|string|max:500',
        ], [
            'dine_in_policies.max' => 'Dine-in Policies must not be greater than 500 characters.',
            'delivery_terms.max' => 'Delivery Terms must not be greater than 500 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the error(s).',
                'errors' => $validator->errors(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 422);
        }

        $business = Business::query()->find($companyId);
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Business not found'], 404);
        }

        $data = $validator->validated();

        $business->dine_in_policies = $this->normalizeMultiline($data['dine_in_policies'] ?? null);
        $business->delivery_terms = $this->normalizeMultiline($data['delivery_terms'] ?? null);
        $business->lastmodified_by = (string) ($user->name ?? '');
        // `utility_company` uses a model that disables automatic `updated_at` management.
        // We still want Terms changes to reflect in the audit footer, so update it explicitly.
        $business->updated_at = now();
        $business->save();

        return response()->json([
            'success' => true,
            'message' => 'Terms & Conditions updated successfully.',
            'data' => [
                'company_id' => (int) $business->id,
                'dine_in_policies' => (string) ($business->dine_in_policies ?? ''),
                'delivery_terms' => (string) ($business->delivery_terms ?? ''),
                'created_at' => $business->created_at,
                'updated_at' => $business->updated_at,
                'created_by' => (string) ($business->created_by ?? ''),
                'lastmodified_by' => (string) ($business->lastmodified_by ?? ''),
            ],
        ]);
    }

    private function normalizeMultiline(?string $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $s = trim((string) $val);
        if ($s === '') {
            return null;
        }
        // Normalize newlines and remove trailing whitespace per line
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $lines = array_map(static fn ($l) => trim((string) $l), explode("\n", $s));
        $lines = array_values(array_filter($lines, static fn ($l) => $l !== ''));
        return implode("\n", $lines);
    }

    private function canAccessCompany($user, int $companyId): bool
    {
        $role = strtolower(trim((string) ($user->role ?? '')));

        // Root Super User (fixed IDs) can access all companies.
        if ($role === 'super admin' && $this->isRootSuperUserId((int) $user->id)) {
            return true;
        }

        // Super Admin (non-root): only companies assigned in utility_company_superuser
        if ($role === 'super admin') {
            try {
                return DB::table('utility_company_superuser')
                    ->where('user_id', (int) $user->id)
                    ->where('company_id', $companyId)
                    ->exists();
            } catch (\Throwable $e) {
                return false;
            }
        }

        // Admin: only the company assigned directly on the user.
        if ($role === 'admin') {
            return (int) ($user->company_id ?? 0) === $companyId;
        }

        return false;
    }

    private function isRootSuperUserId(int $userId): bool
    {
        $ids = (array) config('services.super_users.ids');
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values()->all();
        return in_array($userId, $ids, true);
    }
}
