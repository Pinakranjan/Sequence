<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utility\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Throwable;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Models\Utility\UserLoginRegister;
use App\Services\LoginHistoryService;
use Carbon\Carbon;

class BusinessController extends Controller
{
    public function AllBusinesses()
    {
        if (!$this->currentUserIsRootSuperUser()) {
            abort(403, 'Only fixed super users can access Business Register.');
        }

        return view('admin.utility.business.get_businesses');
    }

    public function ListBusinesses(Request $request)
    {
        if (!$this->currentUserIsRootSuperUser()) {
            return $this->rootOnlyJsonResponse();
        }

        // Precompute used users per business via subquery
        $usedCounts = DB::table('users')
            ->select('company_id', DB::raw('COUNT(*) as used_users'))
            ->whereNotNull('company_id')
            ->groupBy('company_id');

        $businesses = Business::query()
            ->leftJoinSub($usedCounts, 'u', function ($join) {
                $join->on('u.company_id', '=', 'utility_company.id');
            })
            ->orderByDesc('utility_company.id')
            ->select([
                'utility_company.*',
                DB::raw('COALESCE(u.used_users, 0) as used_users'),
            ])
            ->get();

        $result = $businesses->map(function ($row) {
            // Eager load if needed, or derive category name via relationship
            $categoryName = null;
            if ($row->business_category_id) {
                $cat = $row->category;
                $categoryName = $cat ? $cat->name : null;
            }
            return [
                'id' => $row->id,
                'company_code' => $row->company_code,
                'name' => $row->name,
                'business_category_id' => $row->business_category_id,
                'category_name' => $categoryName,
                'address' => $row->address,
                'email' => $row->email,
                'mobile' => $row->mobile,
                'image' => $row->image,
                // Provide S3-aware computed URL for UI (grid, modals, pickers)
                'image_url' => $row->image_url,
                'gstin' => $row->gstin,
                'pan' => $row->pan,
                'dine_in_policies' => $row->dine_in_policies ?? null,
                'delivery_terms' => $row->delivery_terms ?? null,
                'approved_users' => (int) ($row->approved_users ?? 5),
                'used_users' => (int) ($row->used_users ?? 0),
                'is_active' => (bool) $row->is_active,
                'is_locked' => (bool) ($row->is_locked ?? 0),
                'created_by' => $row->created_by,
                'lastmodified_by' => $row->lastmodified_by,
                // Normalize to IST/local timezone before returning
                'created_at' => $this->formatToLocalTz($row->created_at),
                'updated_at' => $this->formatToLocalTz($row->updated_at),
            ];
        })->all();

        // try {
        //     Log::debug('Data', [
        //         'incomingName' => $result,
        //     ]);
        // } catch (Throwable $e) {
        //     // ignore logging failures
        // }

        return response()->json($result);
    }

    /**
     * Used by Master/Transaction pages so limited super admins only see their assigned businesses
     * in the header business picker dropdown. Root super admins still receive the full list.
     */
    public function ListAccessibleBusinessesForPicker()
    {
        $user = Auth::user();
        if (!$user || !$this->isSuperAdminRole($user->role)) {
            return response()->json([
                'success' => false,
                'message' => 'Only Super Admins can access the business picker.',
            ], 403);
        }

        $isRootSuperUser = $this->currentUserIsRootSuperUser();

        if ($isRootSuperUser) {
            $businesses = Business::query()
                ->orderBy('name')
                ->get();
        } else {
            $assignedIds = DB::table('utility_company_superuser')
                ->where('user_id', (int) $user->id)
                ->pluck('company_id')
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values();

            if ($assignedIds->isEmpty()) {
                return response()->json([]);
            }

            $businesses = Business::query()
                ->whereIn('id', $assignedIds->all())
                ->orderBy('name')
                ->get();
        }

        $result = $businesses->map(function (Business $business) {
            return [
                'id' => (int) $business->id,
                'company_code' => (string) ($business->company_code ?? ''),
                'name' => (string) ($business->name ?? ''),
                'image' => $business->image,
                'image_url' => $business->image_url,
                'is_active' => (bool) ($business->is_active ?? 0),
                'is_locked' => (bool) ($business->is_locked ?? 0),
            ];
        })->values();

        return response()->json($result);
    }

    public function AddBusiness(Request $request)
    {
        if (!$this->currentUserIsRootSuperUser()) {
            return $this->rootOnlyJsonResponse();
        }

        $validator = Validator::make($request->all(), [
            'business_category_id' => 'required|exists:utility_business_categories,id',
            'name' => 'required|string|min:10|max:50',
            'address' => 'required|string|max:500',
            'email' => 'required|email|max:100|unique:utility_company,email',
            'mobile' => ['required', 'regex:/^\d{10}$/'],
            'approved_users' => 'required|integer|min:1|max:100',
            // GSTIN: 2 digits (state) + 10 PAN (5 letters, 4 digits, 1 letter) + 1 entity [1-9A-Z] + 'Z' + 1 checksum [0-9A-Z]
            'gstin' => ['nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            // PAN: 5 letters + 4 digits + 1 letter
            'pan' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'nullable|boolean',
        ], [
            'business_category_id.required' => 'Please select a business category.',
            'business_category_id.exists' => 'The selected business category is invalid.',
            'name.required' => 'Please enter the business name.',
            'name.string' => 'The business name must be a valid string.',
            'name.min' => 'The business name must be at least 10 characters.',
            'name.max' => 'The business name may not be greater than 50 characters.',
            'email.required' => 'Please provide a business email address.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered for another business.',
            'email.max' => 'The email may not be greater than 100 characters.',
            'mobile.regex' => 'The mobile number must be a valid 10-digit number.',
            'gstin.regex' => 'The GSTIN must be a valid format.',
            'pan.regex' => 'The PAN must be a valid format.',
            'image.required' => 'Please upload an image for the business logo.',
            'image.max' => 'Image must not be larger than 2 MB.',
            'image.image' => 'The uploaded file must be a valid image.',
            'image.mimes' => 'Image must be a file of type: jpeg, png, jpg, gif, svg.',
            'image.uploaded' => 'Image must not be larger than 2 MB.'
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

        try {
            // Generate unique 10-character alphanumeric business code
            $businessCode = $this->generateUniqueBusinessCode();

            $data = $validator->validated();

            // Normalize casing and formatting
            $data = array_merge($data, [
                'name' => Str::title(Str::lower($data['name'])),
                'business_category_id' => (int) $data['business_category_id'],
                'gstin' => isset($data['gstin']) ? Str::upper($data['gstin']) : null,
                'pan' => isset($data['pan']) ? Str::upper($data['pan']) : null,
                'email' => Str::lower($data['email']),
                'company_code' => $businessCode,
                'created_by' => optional(Auth::user())->name,
            ]);

            // error_log('AddBusiness payload: ' . json_encode($data));

            // Handle logo upload
            if ($request->hasFile('image')) {
                $data['image'] = $this->storeLogo($request->file('image'));
            }

            $business = Business::create($data);
            $business->save();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create business: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Business created successfully',
        ]);
    }

    public function UpdateBusiness(Request $request)
    {
        if (!$this->currentUserIsRootSuperUser()) {
            return $this->rootOnlyJsonResponse();
        }

        // Accept wrapped value payloads (e.g., from UrlAdaptor) and merge
        $value = $request->input('value');
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }
        if (is_array($value)) {
            $request->merge($value);
        }

        // The id can come as 'id' or 'key' (from UrlAdaptor). Prefer explicit 'id'.
        $business_id = $request->input('id', $request->input('key'));
        if (!$business_id) {
            return response()->json([
                'success' => false,
                'message' => 'Missing id',
            ], 422);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required|integer|exists:utility_company,id',
                'business_category_id' => 'required|exists:utility_business_categories,id',
                'name' => 'required|string|min:10|max:50',
                'address' => 'required|string|max:500',
                'email' => 'required|email|max:100|unique:utility_company,email,' . $business_id,
                'mobile' => ['required', 'regex:/^\d{10}$/'],
                'approved_users' => 'required|integer|min:1|max:100',
                'gstin' => ['nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
                'pan' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'is_active' => 'nullable|boolean',
            ],
            [
                'business_category_id.required' => 'Please select a business category.',
                'business_category_id.exists' => 'The selected business category is invalid.',
                'name.required' => 'Please enter the business name.',
                'name.string' => 'The business name must be a valid string.',
                'name.min' => 'The business name must be at least 10 characters.',
                'name.max' => 'The business name may not be greater than 50 characters.',
                'email.required' => 'Please provide a business email address.',
                'email.email' => 'Please provide a valid email address.',
                'email.unique' => 'This email is already registered for another business.',
                'email.max' => 'The email may not be greater than 100 characters.',
                'mobile.regex' => 'The mobile number must be a valid 10-digit number.',
                'gstin.regex' => 'The GSTIN must be a valid format.',
                'pan.regex' => 'The PAN must be a valid format.',
                'image.required' => 'Please upload an image for the business logo.',
                'image.max' => 'Image must not be larger than 2 MB.',
                'image.image' => 'The uploaded file must be a valid image.',
                'image.mimes' => 'Image must be a file of type: jpeg, png, jpg, gif, svg.',
                'image.uploaded' => 'Image must not be larger than 2 MB.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the error(s).',
                'errors' => $validator->errors(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 422);
        }

        $business = Business::findOrFail($business_id);

        // Business rules: can edit only if active and unlocked
        if (!(int) $business->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Business is inactive and cannot be edited.',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }
        if ((int) ($business->is_locked ?? 0) === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Business is locked and cannot be edited.',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        // Prepare new values (excluding image) but don't persist yet; include is_active for accurate dirty check
        $business->fill([
            'name' => Str::title(Str::lower($request->name)),
            'business_category_id' => (int) $request->business_category_id,
            'address' => $request->address,
            'mobile' => $request->mobile,
            'approved_users' => (int) $request->approved_users,
            'gstin' => $request->filled('gstin') ? Str::upper(trim($request->gstin)) : null,
            'pan' => $request->filled('pan') ? Str::upper(trim($request->pan)) : null,
            'email' => Str::lower($request->email),
            'is_active' => $request->boolean('is_active', true) ? 1 : 0,
        ]);

        $imageChanged = $request->hasFile('image');

        // If nothing changed and no new image provided, short-circuit
        if (!$business->isDirty() && !$imageChanged) {
            if (is_array($value)) {
                return response()->json([
                    'id' => $business->id,
                    'company_code' => $business->company_code,
                    'name' => $business->name,
                    'address' => $business->address,
                    'email' => $business->email,
                    'mobile' => $business->mobile,
                    'image' => $business->image,
                    'gstin' => $business->gstin,
                    'pan' => $business->pan,
                    'approved_users' => (int) ($business->approved_users ?? 5),
                    'is_active' => (bool) $business->is_active,
                    'created_by' => $business->created_by,
                    'lastmodified_by' => $business->lastmodified_by,
                    // Convert to IST/local timezone before returning
                    'created_at' => $this->formatToLocalTz($business->created_at),
                    'updated_at' => $this->formatToLocalTz($business->updated_at),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No changes were made to the business!',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ], 422);
        }

        try {
            // If logo uploaded, replace
            if ($imageChanged) {
                // delete old if exists
                if (!empty($business->image)) {
                    $this->deleteLogo($business->image);
                }

                $business->image = $this->storeLogo($request->file('image'));
            }

            $business->lastmodified_by = optional(Auth::user())->name;

            // Never allow changing company_code from update endpoint

            $business->updated_at = now();
            $business->save();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update business: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Business updated successfully',
        ]);
    }

    public function DeleteBusiness(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:utility_company,id']);

        $business = Business::findOrFail($request->integer('id'));
        $old_image = $business->image;

        try {
            // Best-effort removal of logo from S3 and legacy local path
            if (!empty($old_image)) {
                $this->deleteLogo($old_image);
            }
            $business->delete();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete business: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json(['message' => 'Business deleted successfully']);
    }

    private function generateUniqueBusinessCode(): string
    {
        do {
            $code = Str::upper(Str::random(10));
        } while (Business::where('company_code', $code)->exists());
        return $code;
    }

    private function storeLogo($file): string
    {
        // Store directly in S3, keep legacy prefix upload/company_logos/ for existing data
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) {
            $ext = 'jpg';
        }
        $filename = 'logo_' . time() . '_' . Str::random(8) . '.' . $ext;
        $key = 'upload/company_logos/' . $filename;
        try {
            if ($ext !== 'svg') {
                $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                $image = $manager->read($file->getPathname());
                // Constrain to max 300x300 preserving aspect; prevent upsize
                $image->scaleDown(300, 300);
                $encoded = $image->encode();
                Storage::disk('s3')->put($key, (string) $encoded, ['visibility' => 'private']);
            } else {
                Storage::disk('s3')->put($key, file_get_contents($file->getPathname()), ['visibility' => 'private']);
                if (!$this->currentUserIsRootSuperUser()) {
                    return $this->rootOnlyJsonResponse();
                }
            }
        } catch (Throwable $e) {
            throw new \RuntimeException('Failed storing business logo on S3: ' . $e->getMessage());
        }
        return $key;
    }

    private function deleteLogo($path): void
    {
        if (empty($path))
            return;
        $key = ltrim($path, '/');
        // Attempt S3 deletion
        try {
            Storage::disk('s3')->delete($key);
        } catch (Throwable $e) {
        }
        // Legacy local cleanup (safe to remove later)
        try {
            $full = public_path($path);
            if ($path && file_exists($full)) {
                @unlink($full);
            }
        } catch (Throwable $e) {
        }
    }

    // Set active flag for a business (single endpoint for activate/inactivate)
    public function SetActiveBusiness(Request $request)
    {
        $id = $request->input('key', $request->input('id'));

        if (!$id) {
            return response()->json(['error' => 'Missing id'], 422);
        }

        $isActive = $request->input('is_active', null);
        if (!in_array((string) $isActive, ['0', '1'], true)) {
            return response()->json(['error' => 'Invalid is_active value'], 422);
        }

        $row = Business::findOrFail($id);
        $newVal = (int) $isActive;
        if ((int) $row->is_active === $newVal) {
            return response()->json([
                'success' => false,
                'message' => $newVal ? 'Already active.' : 'Already inactive.',
                'alert-type' => 'info',
                'positionClass' => 'toast-bottom-right',
            ]);
        }

        try {
            $row->is_active = $newVal;
            $row->updated_at = now();
            $row->save();
            // Terminate any active sessions for this business when status changes
            $reason = $newVal ? 'BUSINESS ACTIVATED' : 'BUSINESS INACTIVE';
            try {
                app(LoginHistoryService::class)->terminateSessionsByBusiness((int) $row->id, $reason);
            } catch (Throwable $e) {
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $newVal ? 'Business activated successfully.' : 'Business marked inactive successfully.',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }

    // Set locked flag for a business (only allowed if business is active)
    public function SetLockedBusiness(Request $request)
    {
        $id = $request->input('key', $request->input('id'));
        if (!$this->currentUserIsRootSuperUser()) {
            return $this->rootOnlyJsonResponse();
        }

        if (!$id) {
            return response()->json(['error' => 'Missing id'], 422);
        }

        $isLocked = $request->input('is_locked', null);
        if (!in_array((string) $isLocked, ['0', '1'], true)) {
            return response()->json(['error' => 'Invalid is_locked value'], 422);
        }

        $row = Business::findOrFail($id);

        // Business rule: can lock/unlock only if active
        if (!(int) $row->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Only active businesses can be locked or unlocked.',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        $newVal = (int) $isLocked;
        if ((int) ($row->is_locked ?? 0) === $newVal) {
            return response()->json([
                'success' => false,
                'message' => $newVal ? 'Already locked.' : 'Already unlocked.',
                'alert-type' => 'info',
                'positionClass' => 'toast-bottom-right',
            ]);
        }

        try {
            $row->is_locked = $newVal;
            $row->updated_at = now();
            $row->save();
            // Terminate any active sessions for this business when lock state changes
            $reason = $newVal ? 'BUSINESS LOCKED' : 'BUSINESS UNLOCKED';
            try {
                app(LoginHistoryService::class)->terminateSessionsByBusiness((int) $row->id, $reason);
            } catch (Throwable $e) {
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lock status: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $newVal ? 'Business locked successfully.' : 'Business unlocked successfully.',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }

    /**
     * List users for a given business (scoped endpoint, avoids fetching all users)
     * Accepts: company_id (query or body)
     * Returns minimal fields required by Businesses grid Users column
     */
    public function ListBusinessUsers(Request $request)
    {
        if (!$this->currentUserIsRootSuperUser()) {
            return $this->rootOnlyJsonResponse();
        }

        $businessId = $request->query('company_id', $request->input('company_id'));
        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'Missing company_id',
            ], 422);
        }

        // Ensure business exists (restricting to valid id)
        $exists = Business::where('id', $businessId)->exists();
        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid company_id',
            ], 404);
        }

        $users = User::query()
            ->where('company_id', $businessId)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'status', 'is_locked', 'company_id']);

        $result = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'role' => $u->role,
                // Keep original status value but provide a normalized number too for UI logic
                'status' => is_null($u->status) ? null : (string) $u->status,
                'status_num' => (int) ($u->status === true || $u->status === 1 || $u->status === '1'),
                'is_locked' => (bool) $u->is_locked,
                'company_id' => $u->company_id,
            ];
        })->all();

        // try {
        //     Log::debug('ListUsers $result', [
        //         'count' => is_array($result) ? count($result) : 0,
        //         'first5' => array_slice($result, 0, 5),
        //     ]);
        // } catch (Throwable $e) {
        //     // ignore logging failures
        // }
        return response()->json($result);
    }

    /**
     * Format a DateTime/string value to 'Y-m-d H:i:s' in local timezone (default Asia/Kolkata).
     * Accepts Carbon/DateTimeInterface or string (assumed UTC when string).
     */
    private function formatToLocalTz($value, string $tz = 'Asia/Kolkata'): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->setTimezone($tz)->format('Y-m-d H:i:s');
            }
            // Assume incoming string is in UTC if not otherwise specified
            return Carbon::parse((string) $value, 'UTC')->setTimezone($tz)->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            // Fallback to raw string to avoid breaking response
            return is_string($value) ? $value : null;
        }
    }

    /**
     * Purge all data for a business from every table that has a company_id column, excluding the utility_company table.
     * Intended for admin use; ensure route is protected by auth and role middleware.
     */
    public function PurgeBusinessData(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:utility_company,id']);

        if (!$this->currentUserIsRootSuperUser()) {
            return $this->rootOnlyJsonResponse();
        }

        $businessId = (int) $request->input('id');
        $database = config('database.connections.mysql.database');

        try {
            $tables = DB::table('information_schema.COLUMNS')
                ->select('TABLE_NAME')
                ->where('TABLE_SCHEMA', $database)
                ->where('COLUMN_NAME', 'company_id')
                ->pluck('TABLE_NAME')
                ->toArray();
            // Exclude the master business table
            $tables = array_values(array_filter($tables, function ($t) {
                return !in_array($t, ['utility_company']);
            }));

            // Short-circuit if there is no data to clear across any eligible table
            $hasAnyData = false;
            foreach ($tables as $t) {
                try {
                    if (DB::table($t)->where('company_id', $businessId)->exists()) {
                        $hasAnyData = true;
                        break;
                    }
                    if (!$this->currentUserIsRootSuperUser()) {
                        return $this->rootOnlyJsonResponse();
                    }
                } catch (Throwable $e) {
                    // ignore errors for existence checks on problematic tables and continue
                }
            }

            if (!$hasAnyData) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found to clear for the selected business.',
                    'alert-type' => 'warning',
                    'positionClass' => 'toast-bottom-right',
                ], 404);
            }

            DB::beginTransaction();
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            foreach ($tables as $table) {
                try {
                    DB::table($table)->where('company_id', $businessId)->delete();
                } catch (Throwable $e) {
                    // continue; report at end if needed
                }
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();
        } catch (Throwable $e) {
            try {
                DB::rollBack();
            } catch (Throwable $e2) {
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear business data: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'All data for the selected business has been cleared successfully.',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }

    /**
     * Fetch the list of businesses and indicate which ones are assigned to a target super user.
     */
    public function ListSuperUserBusinessAssignments(Request $request)
    {
        if (!$this->currentUserIsRootSuperUser()) {
            return response()->json([
                'success' => false,
                'message' => 'Only fixed super users can manage business access.',
            ], 403);
        }

        $userId = (int) $request->query('user_id', $request->input('user_id', 0));
        if ($userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Target user id is required.',
            ], 422);
        }

        $targetUser = User::find($userId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if (!$this->isSuperAdminRole($targetUser->role)) {
            return response()->json([
                'success' => false,
                'message' => 'Business access can only be managed for Super Admin users.',
            ], 422);
        }

        $assignmentRows = DB::table('utility_company_superuser')
            ->where('user_id', $userId)
            ->get()
            ->keyBy('company_id');

        $businesses = Business::query()
            ->select(['id', 'name', 'company_code', 'is_active', 'is_locked'])
            ->orderBy('name')
            ->get()
            ->map(function (Business $business) use ($assignmentRows) {
                $assignment = $assignmentRows->get($business->id);

                return [
                    'id' => (int) $business->id,
                    'name' => (string) $business->name,
                    'code' => (string) ($business->company_code ?? ''),
                    'is_active' => (int) ($business->is_active ?? 0),
                    'is_locked' => (int) ($business->is_locked ?? 0),
                    'assigned' => $assignment !== null,
                    'assigned_at' => $assignment ? $this->formatToLocalTz($assignment->created_at ?? null) : null,
                    'assigned_by' => $assignment ? (string) ($assignment->created_by ?? '') : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => (int) $targetUser->id,
                'name' => (string) $targetUser->name,
                'email' => (string) ($targetUser->email ?? ''),
                'role' => (string) ($targetUser->role ?? ''),
            ],
            'businesses' => $businesses,
        ]);
    }

    /**
     * Persist business assignments for a target super user (assign/remove in one request).
     */
    public function SyncSuperUserBusinessAssignments(Request $request)
    {
        if (!$this->currentUserIsRootSuperUser()) {
            return response()->json([
                'success' => false,
                'message' => 'Only fixed super users can manage business access.',
            ], 403);
        }

        $userId = (int) $request->input('user_id', 0);
        if ($userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Target user id is required.',
            ], 422);
        }

        $targetUser = User::find($userId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if (!$this->isSuperAdminRole($targetUser->role)) {
            return response()->json([
                'success' => false,
                'message' => 'Business access can only be managed for Super Admin users.',
            ], 422);
        }

        $businessIds = $request->input('company_ids', []);
        if (!is_array($businessIds)) {
            $businessIds = ($businessIds === null || $businessIds === '') ? [] : [$businessIds];
        }

        $businessIdCollection = collect($businessIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        $validBusinessIds = [];
        if ($businessIdCollection->isNotEmpty()) {
            $validBusinessIds = Business::query()
                ->whereIn('id', $businessIdCollection->all())
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            if (count($validBusinessIds) !== $businessIdCollection->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more selected businesses are invalid.',
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($userId, $validBusinessIds) {
                $deleteQuery = DB::table('utility_company_superuser')->where('user_id', $userId);
                if (!empty($validBusinessIds)) {
                    $deleteQuery->whereNotIn('company_id', $validBusinessIds)->delete();
                } else {
                    $deleteQuery->delete();
                }

                $existingAssignments = DB::table('utility_company_superuser')
                    ->where('user_id', $userId)
                    ->pluck('company_id')
                    ->map(fn($id) => (int) $id)
                    ->all();

                $toInsert = array_values(array_diff($validBusinessIds, $existingAssignments));
                if (!empty($toInsert)) {
                    $now = now();
                    $creator = $this->currentUserDisplayName();
                    $payload = array_map(function ($businessId) use ($userId, $creator, $now) {
                        return [
                            'user_id' => $userId,
                            'company_id' => $businessId,
                            'created_by' => $creator,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }, $toInsert);

                    DB::table('utility_company_superuser')->insert($payload);
                }
            });
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update business assignments: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Business access updated successfully.',
            'assigned_count' => count($validBusinessIds),
        ]);
    }

    private function rootOnlyJsonResponse(?string $message = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? 'Only fixed super users can perform this action.',
            'alert-type' => 'error',
            'positionClass' => 'toast-bottom-right',
        ], 403);
    }

    private function currentUserIsRootSuperUser(): bool
    {
        $ids = collect(config('services.super_users.ids') ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $currentId = (int) (Auth::id() ?? 0);
        return $currentId > 0 && in_array($currentId, $ids, true);
    }

    private function isSuperAdminRole(?string $role): bool
    {
        $normalized = strtolower(trim((string) $role));
        return in_array($normalized, ['super admin'], true);
    }

    private function currentUserDisplayName(): string
    {
        $user = Auth::user();
        $name = trim((string) optional($user)->name);
        if ($name !== '') {
            return $name;
        }
        $id = (int) optional($user)->id;
        return $id ? 'USER#' . $id : 'SYSTEM';
    }
}
