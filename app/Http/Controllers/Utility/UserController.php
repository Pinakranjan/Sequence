<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Utility\UserLoginRegister;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UserController extends Controller
{
    public function AllUsers()
    {
        return view('admin.utility.user.get_users');
    }

    /**
     * Return list of users for grid.
     * Now includes Super Admin users as well, but excludes the currently logged-in user.
     */
    public function ListUsers(Request $request)
    {
        $actorContext = $this->getActorContext();
        $limitedBusinessIds = $this->getLimitedBusinessIds($actorContext);

        $rows = $this->buildUserQuery($actorContext, $limitedBusinessIds);
        $userIds = $rows->pluck('id')->filter()->all();

        $permMap = $this->buildPermissionMap($userIds);
        $superBusinessMap = $this->buildSuperBusinessMap($userIds);

        $out = $this->formatUserRows($rows, $permMap, $superBusinessMap);

        return response()->json($out);
    }

    /**
     * Get the current actor's context for authorization checks.
     *
     * @return array{id: int, role: string, isSuperAdmin: bool, isRootSuperUser: bool}
     */
    private function getActorContext(): array
    {
        $actor = Auth::user();
        $actorId = (int) optional($actor)->id;
        $actorRole = strtolower(trim((string) optional($actor)->role));
        $isSuperAdmin = in_array($actorRole, ['super admin'], true);

        $rootSuperUserIds = collect(config('services.super_users.ids') ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $isRootSuperUser = $isSuperAdmin && $actorId > 0 && in_array($actorId, $rootSuperUserIds, true);

        return [
            'id' => $actorId,
            'role' => $actorRole,
            'isSuperAdmin' => $isSuperAdmin,
            'isRootSuperUser' => $isRootSuperUser,
        ];
    }

    /**
     * Get limited Business IDs for non-root super admins.
     *
     * @param array $actorContext
     * @return array
     */
    private function getLimitedBusinessIds(array $actorContext): array
    {
        if (!$actorContext['isSuperAdmin'] || $actorContext['isRootSuperUser']) {
            return [];
        }

        return DB::table('utility_company_superuser')
            ->where('user_id', $actorContext['id'])
            ->pluck('company_id')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values()
            ->all();
    }

    /**
     * Build and execute the user query with joins and filters.
     *
     * @param array $actorContext
     * @param array $limitedBusinessIds
     * @return \Illuminate\Support\Collection
     */
    private function buildUserQuery(array $actorContext, array $limitedBusinessIds)
    {
        // Subquery: latest open session row id per user (if any)
        $latestOpen = DB::table('utility_user_login_register as t')
            ->select('t.user_id', DB::raw('MAX(t.id) as last_row_id'))
            ->whereNull('t.session_end_time')
            ->groupBy('t.user_id');

        return User::query()
            ->leftJoin('utility_company as c', 'c.id', '=', 'users.company_id')
            ->leftJoinSub($latestOpen, 'lo', function ($join) {
                $join->on('lo.user_id', '=', 'users.id');
            })
            ->leftJoin('utility_user_login_register as lh', function ($join) {
                $join->on('lh.user_id', '=', 'users.id');
                $join->on('lh.id', '=', 'lo.last_row_id');
            })
            // Exclude current authenticated user from the listing
            ->when(Auth::check(), function ($q) {
                $q->where('users.id', '<>', Auth::id());
            })
            // If the requester is an ADMIN, limit records to same business
            ->when(Auth::check() && strtolower(trim((string) optional(Auth::user())->role)) === 'admin', function ($q) {
                $q->where('users.company_id', optional(Auth::user())->company_id);
            })
            ->when($actorContext['isSuperAdmin'] && !$actorContext['isRootSuperUser'], function ($q) use ($limitedBusinessIds) {
                if (empty($limitedBusinessIds)) {
                    $q->whereRaw('1 = 0');
                    return;
                }

                $q->whereIn('users.company_id', $limitedBusinessIds)
                    ->where(function ($sub) {
                        $sub->whereRaw('LOWER(users.role) = ?', ['user'])
                            ->orWhereRaw('LOWER(users.role) = ?', ['admin']);
                    });
            })
            ->orderByDesc('users.id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.address',
                'users.role',
                'users.status',
                'users.is_locked',
                'users.photo',
                'users.company_id',
                DB::raw('COALESCE(c.name, "") as company_name'),
                DB::raw('COALESCE(c.company_code, "") as company_code'),
                DB::raw('COALESCE(c.is_active, 0) as company_is_active'),
                DB::raw('COALESCE(c.is_locked, 0) as company_is_locked'),
                'users.created_at',
                'users.updated_at',
                // login history fields (if open session exists)
                DB::raw('lh.session_id as lh_session_id'),
                DB::raw('lh.login_time as lh_login_time'),
                DB::raw('lh.last_connected_time as lh_last_connected_time'),
                DB::raw('lh.system_name as lh_system_name'),
            ])
            ->get();
    }

    /**
     * Build permission counts map for the given user IDs.
     *
     * @param array $userIds
     * @return array
     */
    private function buildPermissionMap(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $permMap = [];

        $permCounts = DB::table('utility_user_permission_register as p')
            ->join('utility_forms as f', 'f.id', '=', 'p.form_id')
            ->whereIn('p.user_id', $userIds)
            // count only rows where at least one permission is granted
            ->where(function ($q) {
                $q->where('p.is_add', 1)
                    ->orWhere('p.is_edit', 1)
                    ->orWhere('p.is_delete', 1)
                    ->orWhere('p.is_view', 1)
                    ->orWhere('p.is_viewatt', 1);
            })
            ->groupBy('p.user_id', 'f.group_name')
            ->select('p.user_id', 'f.group_name', DB::raw('COUNT(*) as cnt'))
            ->get();

        foreach ($permCounts as $r) {
            $uid = (int) $r->user_id;
            $g = strtolower((string) ($r->group_name ?? ''));
            $key = null;
            if (str_starts_with($g, 'master')) {
                $key = 'M';
            } elseif (str_starts_with($g, 'report')) {
                $key = 'R';
            } elseif (str_starts_with($g, 'utility')) {
                $key = 'U';
            }
            if ($key === null) {
                continue;
            }
            if (!isset($permMap[$uid])) {
                $permMap[$uid] = ['M' => 0, 'R' => 0, 'U' => 0];
            }
            $permMap[$uid][$key] = (int) $r->cnt;
        }

        return $permMap;
    }

    /**
     * Build super Business assignments map for the given user IDs.
     *
     * @param array $userIds
     * @return array
     */
    private function buildSuperBusinessMap(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $superBusinessMap = [];

        $assignmentRows = DB::table('utility_company_superuser as su')
            ->join('utility_company as c', 'c.id', '=', 'su.company_id')
            ->whereIn('su.user_id', $userIds)
            ->orderBy('c.name')
            ->select([
                'su.user_id',
                'c.id as company_id',
                'c.name as company_name',
                'c.company_code',
                'c.is_active',
                'c.is_locked',
            ])
            ->get();

        foreach ($assignmentRows as $assignment) {
            $uid = (int) $assignment->user_id;
            if (!isset($superBusinessMap[$uid])) {
                $superBusinessMap[$uid] = [];
            }
            $superBusinessMap[$uid][] = [
                'id' => (int) ($assignment->company_id ?? 0),
                'name' => (string) ($assignment->company_name ?? ''),
                'code' => (string) ($assignment->company_code ?? ''),
                'is_active' => (int) ($assignment->is_active ?? 0),
                'is_locked' => (int) ($assignment->is_locked ?? 0),
            ];
        }

        return $superBusinessMap;
    }



    /**
     * Format user rows for JSON response.
     *
     * @param \Illuminate\Support\Collection $rows
     * @param array $permMap
     * @param array $superBusinessMap
     * @return array
     */
    private function formatUserRows($rows, array $permMap, array $superBusinessMap): array
    {
        $awsBase = config('filesystems.disks.s3.url') ?? env('AWS_URL');
        $useSigned = (bool) env('AWS_SIGNED_URLS', false);

        return $rows->map(function ($r) use ($permMap, $awsBase, $useSigned, $superBusinessMap) {
            return $this->formatUserRow($r, $permMap, $superBusinessMap, $awsBase, $useSigned);
        })->all();
    }

    /**
     * Format a single user row for JSON response.
     *
     * @param mixed $r User row from query
     * @param array $permMap
     * @param array $superBusinessMap
     * @param string|null $awsBase
     * @param bool $useSigned
     * @return array
     */
    private function formatUserRow($r, array $permMap, array $superBusinessMap, ?string $awsBase, bool $useSigned): array
    {
        $activeSince = $this->calculateActiveSince($r->lh_login_time);
        $photoUrl = $this->getPhotoUrl($r->photo, $awsBase, $useSigned);

        $assignedBusinesses = $superBusinessMap[$r->id] ?? [];
        $assignedBusinessNames = collect($assignedBusinesses)
            ->map(fn($business) => trim((string) (is_array($business) ? ($business['name'] ?? '') : ($business->name ?? ''))))
            ->filter()
            ->values()
            ->all();

        $businessColumnText = '';
        if (!empty($assignedBusinessNames)) {
            $businessColumnText = implode(', ', $assignedBusinessNames);
        } elseif (!empty($r->company_name)) {
            $businessColumnText = (string) $r->company_name;
        }

        return [
            'id' => $r->id,
            'name' => $r->name,
            'email' => $r->email,
            'phone' => $r->phone,
            'address' => $r->address,
            'role' => Str::upper($r->role),
            // Keep status as boolean-compatible for UI toggles
            'status' => in_array((string) ($r->status), ['1', 1, true, 'true'], true) ? 1 : 0,
            'is_locked' => (bool) ($r->is_locked ?? 0),
            'photo' => $r->photo,
            'photo_url' => $photoUrl,
            'company_id' => $r->company_id,
            'company_name' => $r->company_name,
            'company_code' => $r->company_code,
            'company_is_active' => (int) ($r->company_is_active ?? 0),
            'company_is_locked' => (int) ($r->company_is_locked ?? 0),
            'created_at' => $r->created_at,
            'updated_at' => $r->updated_at,
            // Active session metadata (if any)
            'active_session' => $r->lh_session_id ? 1 : 0,
            'session_id' => $r->lh_session_id,
            'login_time' => $r->lh_login_time,
            'last_connected_time' => $r->lh_last_connected_time,
            // Pass display fields as-is without timezone conversion or reformatting
            'login_time_display' => $r->lh_login_time,
            'last_connected_display' => $r->lh_last_connected_time,
            'active_since' => $activeSince,
            'system_name' => $r->lh_system_name,
            // Permission summary counts for pill display in UI
            'perm_counts' => $permMap[$r->id] ?? ['M' => 0, 'T' => 0, 'R' => 0, 'U' => 0],
            'super_businesses' => $assignedBusinesses,
            'super_business_count' => count($assignedBusinesses),
            'super_business_names' => $assignedBusinessNames,
            'company_column_text' => $businessColumnText,
        ];
    }

    /**
     * Calculate the "active since" duration text from login time.
     *
     * @param string|null $loginTime
     * @return string
     */
    private function calculateActiveSince(?string $loginTime): string
    {
        if (!$loginTime) {
            return '';
        }

        try {
            $loginAtRaw = \Carbon\Carbon::parse($loginTime);
            $diffSec = max(0, $loginAtRaw->diffInSeconds(\Carbon\Carbon::now()));
            $mins = intdiv($diffSec, 60);
            $hrs = intdiv($mins, 60);
            $days = intdiv($hrs, 24);
            $mins = $mins % 60;
            $hrs = $hrs % 24;
            return ($days ? ($days . 'd ') : '') . ($hrs ? ($hrs . 'h ') : '') . ($mins . 'm');
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Get the photo URL for a user.
     *
     * @param string|null $photoFilename
     * @param string|null $awsBase
     * @param bool $useSigned
     * @return string
     */
    private function getPhotoUrl(?string $photoFilename, ?string $awsBase, bool $useSigned): string
    {
        if (!$photoFilename) {
            return url('upload/no_image.jpg');
        }

        $key = 'upload/user_images/' . ltrim($photoFilename, '/');

        if ($useSigned) {
            try {
                $disk = Storage::disk('s3');
                if (is_callable([$disk, 'temporaryUrl'])) {
                    return (string) call_user_func([$disk, 'temporaryUrl'], $key, now()->addMinutes((int) env('AWS_SIGNED_URL_TTL', 60)));
                }
                return url($key);
            } catch (Throwable $e) {
                return url($key);
            }
        }

        if ($awsBase) {
            return rtrim($awsBase, '/') . '/' . ltrim($key, '/');
        }

        return url($key);
    }

    /**
     * Delete a user by id. Also removes stored photo file if present.
     */
    public function DeleteUser(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:users,id',
        ]);

        // Only Super Admin can delete users
        $actor = Auth::user();
        $actorRole = strtolower(trim((string) optional($actor)->role));
        if ($actorRole !== 'super admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only Super Admin can delete users.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        $user = User::findOrFail($request->integer('id'));

        // Prevent deleting super admin accounts (not listed anyway, but double-safety)
        $role = strtolower(trim((string) ($user->role ?? '')));
        if ($role === 'super admin') {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin cannot be deleted.',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        // If a photo exists, attempt to delete from S3 first then local
        $photo = $user->photo;
        try {
            if (!empty($photo)) {
                try {
                    Storage::disk('s3')->delete('upload/user_images/' . $photo);
                } catch (Throwable $e) { /* ignore */
                }
                $full = public_path('upload/user_images/' . $photo);
                if (is_file($full)) {
                    @unlink($full);
                }
            }
        } catch (Throwable $e) { /* ignore */
        }

        // Best effort: end any active sessions for this user before deletion
        try {
            UserLoginRegister::where('user_id', $user->id)
                ->whereNull('session_end_time')
                ->update([
                    'session_end_time' => now(),
                    'session_end_type' => 'SESSION.REVOKED',
                    'logout_time' => now()->toDateTimeString(),
                ]);
        } catch (Throwable $e) { /* optional */
        }

        try {
            $user->delete();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Revoke a user's active session (admin-only). Marks session_end_type='SESSION.REVOKED'.
     */
    public function RevokeSession(Request $request)
    {
        $actor = Auth::user();
        if (!$actor || !in_array(strtolower(trim((string) optional($actor)->role)), ['admin', 'super admin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to revoke sessions.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'session_id' => 'required|string|max:100',
        ]);

        try {
            $row = UserLoginRegister::where('user_id', $data['user_id'])
                ->where('session_id', $data['session_id'])
                ->whereNull('session_end_time')
                ->orderByDesc('id')
                ->first();

            if (!$row) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active session not found or already ended.',
                    'alert-type' => 'warning',
                    'positionClass' => 'toast-bottom-right',
                ], 404);
            }

            $row->session_end_time = now();
            $row->session_end_type = 'SESSION.REVOKED';
            $row->logout_time = now()->toDateTimeString();
            $row->save();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke session: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully.',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }

    /**
     * Update an existing user. Email is not allowed to be changed here.
     */
    public function UpdateUser(Request $request)
    {
        // Accept wrapped payloads
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

        $userId = (int) $request->input('id', $request->input('key'));

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing id'], 422);
        }

        $user = User::findOrFail($userId);
        $oldRole = strtolower(trim((string) ($user->role ?? '')));

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'name' => 'required|string|min:3|max:100',
            'address' => 'required|string|max:500',
            // Email cannot be changed; validate format if sent but do not update
            'email' => 'nullable|email|max:100',
            'phone' => ['required', 'regex:/^\d{10}$/'],
            'company_id' => 'required|integer|exists:utility_company,id',
            'role' => 'required|in:user,admin',
            // Photo is optional in edit, but must be a valid image if provided
            'photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            // status comes as checkbox boolean; coerce below
            'status' => 'nullable',
        ], [
            'name.required' => 'Please enter the name.',
            'name.min' => 'Name must be at least 3 characters.',
            'name.max' => 'Name may not be greater than 100 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email may not be greater than 100 characters.',
            'address.required' => 'Please enter the address.',
            'phone.required' => 'Please enter the phone number.',
            'phone.regex' => 'Phone must be a valid 10-digit number.',
            'company_id.required' => 'Please select a business.',
            'company_id.exists' => 'Please select a valid business.',
            'role.in' => 'Role must be USER or ADMIN.',
            'photo.image' => 'The uploaded file must be a valid image.',
            'photo.mimes' => 'Image must be a file of type: jpeg, png, jpg, gif, svg, webp.',
            'photo.max' => 'Image must not be larger than 2 MB.',
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

        $data = $validator->validated();

        // Enforce email immutability here
        $keepEmail = $user->email;

        // Do not allow editing if user is locked or not active (status = 0)
        $active = in_array((string) $user->status, ['1', 1, true, 'true'], true);
        $locked = (int) ($user->is_locked ?? 0) === 1;

        if (!$active || $locked) {
            return response()->json([
                'success' => false,
                'message' => 'Only active and unlocked users can be edited.',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        // Fill updatable fields (but do not persist yet; we will check dirty changes)
        $user->name = Str::title(Str::lower($data['name']));
        $user->address = $data['address'] ?? null;
        $user->phone = $data['phone'] ?? null;
        $user->company_id = $data['company_id'] ?? null;
        $user->role = $data['role'];

        // status mapping similar to businesses is_active
        if ($request->has('status')) {
            $val = $request->input('status');
            $bool = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $user->status = ($bool === null) ? ($val ? '1' : '0') : ($bool ? '1' : '0');
        }

        // Restore email to ensure it's unchanged
        $user->email = $keepEmail;

        // Track image intent
        $imageChanged = $request->hasFile('photo');
        $photoRemoved = (bool) $request->boolean('remove_photo', false);

        // If nothing changed and no new image provided and not removing image, short-circuit like BusinessController::UpdateBusiness
        if (!$user->isDirty() && !$imageChanged && !$photoRemoved) {
            if (is_array($value)) {
                return response()->json([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'role' => $user->role,
                    'status' => in_array((string) $user->status, ['1', 1, true, 'true'], true) ? 1 : 0,
                    'is_locked' => (bool) ($user->is_locked ?? 0),
                    'photo' => $user->photo,
                    'company_id' => $user->company_id,
                    'created_at' => optional($user->created_at)->toDateTimeString(),
                    'updated_at' => optional($user->updated_at)->toDateTimeString(),
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'No changes were made to the user!',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ], 422);
        }

        // Photo removal handling
        if ($photoRemoved) {
            try {
                if (!empty($user->photo)) {
                    try {
                        Storage::disk('s3')->delete('upload/user_images/' . $user->photo);
                    } catch (Throwable $e) {
                    }
                    $oldFullPath = public_path('upload/user_images/' . $user->photo);
                    if (is_file($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }
            } catch (Throwable $e) { /* ignore deletion failure */
            }
            $user->photo = null;
        }

        // Photo upload handling (overrides removal if a new file is provided)
        if ($imageChanged) {
            $this->replaceUserPhoto($user, $request->file('photo'));
        }

        $user->lastmodified_by = optional(Auth::user())->name;
        $user->updated_at = now();

        try {
            $user->save();
            // If role changed, handle role-specific cleanup
            $newRole = strtolower(trim((string) ($user->role ?? '')));
            if ($oldRole !== $newRole) {
                // If becoming admin, clear old permission register rows
                if ($oldRole !== 'admin' && $newRole === 'admin') {
                    try {
                        DB::table('utility_user_permission_register')->where('user_id', $user->id)->delete();
                    } catch (Throwable $e) { /* best-effort cleanup */
                    }
                }
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
        ]);
    }

    /**
     * Toggle user status (active/inactive) using the same semantics as business is_active.
     */
    public function SetActiveUser(Request $request)
    {
        $id = $request->input('key', $request->input('id'));

        if (!$id) {
            return response()->json(['error' => 'Missing id'], 422);
        }

        $isActive = $request->input('is_active', null);
        if (!in_array((string) $isActive, ['0', '1'], true)) {
            return response()->json(['error' => 'Invalid is_active value'], 422);
        }

        $row = User::findOrFail($id);
        $newVal = (int) $isActive;
        $cur = in_array((string) $row->status, ['1', 1, true, 'true'], true) ? 1 : 0;

        if ($cur === $newVal) {
            return response()->json([
                'success' => false,
                'message' => $newVal ? 'Already active.' : 'Already inactive.',
                'alert-type' => 'info',
                'positionClass' => 'toast-bottom-right',
            ]);
        }

        try {

            $row->status = $newVal ? '1' : '0';
            $row->updated_at = now();
            $row->save();

            // If user was just made inactive, terminate any active login sessions
            if ($newVal === 0) {
                try {
                    UserLoginRegister::where('user_id', $row->id)
                        ->whereNull('session_end_time')
                        ->update([
                            'session_end_time' => now(),
                            'session_end_type' => 'SESSION.REVOKED',
                            'logout_time' => now()->toDateTimeString(),
                        ]);
                } catch (Throwable $e) { /* best-effort; ignore */
                }
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
            'message' => $newVal ? 'User activated successfully.' : 'User marked inactive successfully.',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }

    /**
     * Set locked flag for a user (only allowed if user is active)
     */
    public function SetLockedUser(Request $request)
    {
        $id = $request->input('key', $request->input('id'));
        if (!$id) {
            return response()->json(['error' => 'Missing id'], 422);
        }

        $isLocked = $request->input('is_locked', null);
        if (!in_array((string) $isLocked, ['0', '1'], true)) {
            return response()->json(['error' => 'Invalid is_locked value'], 422);
        }

        $row = User::findOrFail($id);

        // Business rule: can lock/unlock only if active
        $active = in_array((string) $row->status, ['1', 1, true, 'true'], true);
        if (!$active) {
            return response()->json([
                'success' => false,
                'message' => 'Only active users can be locked or unlocked.',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        $newVal = (int) $isLocked;
        $cur = (int) ($row->is_locked ?? 0);
        if ($cur === $newVal) {
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

            // If user was just locked, terminate any active login sessions
            if ($newVal === 1) {
                try {
                    UserLoginRegister::where('user_id', $row->id)
                        ->whereNull('session_end_time')
                        ->update([
                            'session_end_time' => now(),
                            'session_end_type' => 'SESSION.REVOKED',
                            'logout_time' => now()->toDateTimeString(),
                        ]);
                } catch (Throwable $e) { /* best-effort; ignore */
                }
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
            'message' => $newVal ? 'User locked successfully.' : 'User unlocked successfully.',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }

    private function replaceUserPhoto(User $user, $file): void
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file)->resize(200, 200);
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name_gen = hexdec(uniqid()) . '.' . $extension;
        $pathKey = 'upload/user_images/' . $name_gen;

        // Persist resized image to a temporary file to obtain binary safely (Intervention v3)
        try {
            $tempExt = in_array($extension, ['png', 'webp']) ? $extension : 'jpg';
            $tempPath = sys_get_temp_dir() . '/' . uniqid('usr_photo_', true) . '.' . $tempExt;
            $image->save($tempPath); // auto-select encoder based on extension
            $binary = @file_get_contents($tempPath);
            @unlink($tempPath);
            if ($binary === false || strlen($binary) === 0) {
                throw new \RuntimeException('Empty binary after image processing.');
            }
            $contentType = $file->getMimeType() ?: ('image/' . $tempExt);
            if ($tempExt === 'jpg') {
                $contentType = 'image/jpeg';
            }
        } catch (Throwable $e) {
            throw new \RuntimeException('Failed to process image: ' . $e->getMessage());
        }

        $storedOnS3 = false;

        // S3 only: upload or throw; no local fallback
        if (!config('filesystems.disks.s3.bucket')) {
            throw new \RuntimeException('S3 storage is not configured.');
        }

        try {
            // Prefer ACL-less first (visibility omitted); some buckets still require public ACL
            $storedOnS3 = Storage::disk('s3')->put($pathKey, $binary, [
                'ContentType' => $contentType,
            ]);
            if (!$storedOnS3) {
                $storedOnS3 = Storage::disk('s3')->put($pathKey, $binary, [
                    'visibility' => 'public',
                    'ContentType' => $contentType,
                ]);
            }
        } catch (Throwable $e) {
            // Retry once without visibility if exception occurred in first attempt
            try {
                $storedOnS3 = Storage::disk('s3')->put($pathKey, $binary, ['ContentType' => $contentType]);
            } catch (Throwable $e2) {
                $storedOnS3 = false;
            }
        }

        if (!$storedOnS3) {
            throw new \RuntimeException('Failed storing image on S3.');
        }

        // On success: delete old image from S3 and any legacy local copy (best effort)
        try {
            if ($user->photo) {
                Storage::disk('s3')->delete('upload/user_images/' . $user->photo);
                $oldLocal = public_path('upload/user_images/' . $user->photo);
                if (is_file($oldLocal)) {
                    @unlink($oldLocal);
                }
            }
        } catch (Throwable $e) { /* ignore cleanup failures */
        }

        $user->photo = $name_gen;
        return;
    }

    /**
     * List assignable forms for a user along with current permissions.
     * Filters utility_forms where is_active=1, is_assignable=1 and (is_released|is_realesed)=1.
     * Returns one row per form with flags to indicate whether a permission checkbox should be shown
     * (is_add/is_edit/is_delete/is_view/is_viewatt) and the user's current selections in add2/edit2/... fields.
     */
    public function ListUserForms(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing user_id'], 422);
        }

        // Determine which release flag column exists to avoid unknown column SQL errors
        $hasReleased = Schema::hasColumn('utility_forms', 'is_released');
        $hasRealesed = Schema::hasColumn('utility_forms', 'is_realesed');

        // Base forms with left join to current user's permissions
        $formsQuery = DB::table('utility_forms as f')
            ->leftJoin('utility_user_permission_register as p', function ($join) use ($userId) {
                $join->on('p.form_id', '=', 'f.id')
                    ->where('p.user_id', '=', $userId);
            })
            ->where('f.is_active', 1)
            ->where('f.is_assignable', 1)
            // Handle the release flag (either "is_released" or legacy typo "is_realesed")
            ->when($hasReleased, function ($q) {
                $q->where('f.is_released', 1);
            })
            ->when(!$hasReleased && $hasRealesed, function ($q) {
                $q->where('f.is_realesed', 1);
            })
            ->orderBy('f.group_name')
            ->orderBy('f.subgroup_name')
            ->orderBy('f.form_name');

        // Build select list and include lastmodified_by only if the column exists (avoid unknown column errors)
        $selects = [
            'f.id',
            'f.form_name',
            'f.group_name',
            'f.subgroup_name',
            DB::raw('COALESCE(f.is_add,0) as is_add'),
            DB::raw('COALESCE(f.is_edit,0) as is_edit'),
            DB::raw('COALESCE(f.is_delete,0) as is_delete'),
            DB::raw('COALESCE(f.is_view,0) as is_view'),
            DB::raw('COALESCE(f.is_viewatt,0) as is_viewatt'),
            // current user selections (default 0)
            DB::raw('COALESCE(p.is_add,0) as add2'),
            DB::raw('COALESCE(p.is_edit,0) as edit2'),
            DB::raw('COALESCE(p.is_viewatt,0) as viewatt2'),
            DB::raw('COALESCE(p.is_delete,0) as delete2'),
            DB::raw('COALESCE(p.is_view,0) as view2'),
            DB::raw('COALESCE(p.id,0) as permission_row_id'),
            // audit fields from permission register (may be null if no row yet)
            DB::raw('p.created_at as created_at'),
            DB::raw('p.created_by as created_by'),
            DB::raw('p.updated_at as updated_at'),
        ];

        $hasLastModifiedByCol = Schema::hasColumn('utility_user_permission_register', 'last_modified_by');
        $hasLastmodifiedByCol = Schema::hasColumn('utility_user_permission_register', 'lastmodified_by');
        if ($hasLastModifiedByCol) {
            $selects[] = DB::raw('p.last_modified_by as lastmodified_by');
        } elseif ($hasLastmodifiedByCol) {
            $selects[] = DB::raw('p.lastmodified_by as lastmodified_by');
        } else {
            $selects[] = DB::raw('NULL as lastmodified_by');
        }

        $formsQuery->select($selects);

        $forms = $formsQuery->get();

        $out = $forms->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'name' => $r->form_name,
                'group' => $r->group_name,
                'subgroup' => $r->subgroup_name,
                'is_add' => (int) $r->is_add === 1,
                'is_edit' => (int) $r->is_edit === 1,
                'is_delete' => (int) $r->is_delete === 1,
                'is_view' => (int) $r->is_view === 1,
                'is_viewatt' => (int) $r->is_viewatt === 1,
                'add2' => (int) $r->add2 === 1,
                'edit2' => (int) $r->edit2 === 1,
                'delete2' => (int) $r->delete2 === 1,
                'view2' => (int) $r->view2 === 1,
                'viewatt2' => (int) $r->viewatt2 === 1,
                'created_at' => $r->created_at,
                'created_by' => $r->created_by,
                'updated_at' => $r->updated_at,
                'lastmodified_by' => $r->lastmodified_by,
            ];
        })->all();

        return response()->json($out);
    }

    /**
     * Save user's selected permissions into utility_user_permission_register
     */
    public function SaveUserPermissions(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permissions' => 'required|string', // JSON string
        ]);

        $perms = json_decode($data['permissions'], true);
        if (!is_array($perms)) {
            return response()->json(['success' => false, 'message' => 'Invalid permissions payload'], 422);
        }

        $userId = (int) $data['user_id'];
        $actor = optional(Auth::user())->name;

        DB::beginTransaction();
        try {
            $changes = 0;
            foreach ($perms as $row) {
                $formId = (int) ($row['id'] ?? $row['form_id'] ?? 0);
                if ($formId <= 0) {
                    continue;
                }

                // Only persist rows where at least one permission is set
                $vals = [
                    'is_add' => !empty($row['add2']) ? 1 : 0,
                    'is_edit' => !empty($row['edit2']) ? 1 : 0,
                    'is_delete' => !empty($row['delete2']) ? 1 : 0,
                    'is_view' => !empty($row['view2']) ? 1 : 0,
                    'is_viewatt' => !empty($row['viewatt2']) ? 1 : 0,
                ];
                $hasAny = array_sum(array_values($vals)) > 0;

                $existing = DB::table('utility_user_permission_register')
                    ->where('user_id', $userId)
                    ->where('form_id', $formId)
                    ->first();

                // detect optional columns on the table only once
                static $hasUpdatedBy = null, $hasLastModifiedBy = null, $hasLastmodifiedByLegacy = null;
                if ($hasUpdatedBy === null) {
                    $hasUpdatedBy = Schema::hasColumn('utility_user_permission_register', 'updated_by');
                    $hasLastModifiedBy = Schema::hasColumn('utility_user_permission_register', 'last_modified_by');
                    $hasLastmodifiedByLegacy = Schema::hasColumn('utility_user_permission_register', 'lastmodified_by');
                }

                if ($hasAny) {
                    if ($existing) {
                        // Only update if something actually changed
                        $differs = (
                            (int) $existing->is_add !== (int) $vals['is_add'] ||
                            (int) $existing->is_edit !== (int) $vals['is_edit'] ||
                            (int) $existing->is_delete !== (int) $vals['is_delete'] ||
                            (int) $existing->is_view !== (int) $vals['is_view'] ||
                            (int) $existing->is_viewatt !== (int) $vals['is_viewatt']
                        );
                        if ($differs) {
                            $update = array_merge($vals, ['updated_at' => now()]);
                            if ($hasUpdatedBy) {
                                $update['updated_by'] = $actor;
                            }
                            if ($hasLastModifiedBy) {
                                $update['last_modified_by'] = $actor;
                            }
                            if ($hasLastmodifiedByLegacy) {
                                $update['lastmodified_by'] = $actor;
                            }
                            DB::table('utility_user_permission_register')
                                ->where('id', $existing->id)
                                ->update($update);
                            $changes++;
                        }
                    } else {
                        // Initial insert: only set created_* fields. Do NOT set updated_at/lastmodified_by yet.
                        $insert = array_merge($vals, [
                            'user_id' => $userId,
                            'form_id' => $formId,
                            'created_by' => $actor,
                            'created_at' => now(),
                        ]);
                        DB::table('utility_user_permission_register')->insert($insert);
                        $changes++;
                    }
                } else {
                    if ($existing) {
                        // If no permissions selected, remove row to keep table lean
                        DB::table('utility_user_permission_register')->where('id', $existing->id)->delete();
                        $changes++;
                    }
                }
            }

            // If there were changes, stamp the user record
            if ($changes > 0) {
                DB::table('users')
                    ->where('id', $userId)
                    ->update([
                        'lastmodified_by' => $actor,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();
            if ($changes === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nothing changed.',
                    'alert-type' => 'warning',
                    'positionClass' => 'toast-bottom-right',
                ], 200);
            }
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save permissions: ' . $e->getMessage(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permissions saved successfully.',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }
}
