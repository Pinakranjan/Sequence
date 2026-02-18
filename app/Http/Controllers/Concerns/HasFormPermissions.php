<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Throwable;

trait HasFormPermissions
{
    /**
     * Check permissions for role 'user' using utility_user_permission_register.
     * Admin and Super Admin bypass checks.
     */
    protected function canDo($user, string $action, int $form_id): bool
    {
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        if (in_array($normRole, ['admin', 'super admin'], true))
            return true;

        $field = match ($action) {
            'add' => 'is_add',
            'edit' => 'is_edit',
            'delete' => 'is_delete',
            'view' => 'is_view',
            'viewatt' => 'is_viewatt',
            default => null,
        };
        if (!$field)
            return false;

        try {
            $row = DB::table('utility_user_permission_register')
                ->where('user_id', $user->id)
                ->where('form_id', $form_id)
                ->select($field)
                ->first();
            return $row && (int) ($row->{$field} ?? 0) === 1;
        } catch (Throwable $e) {
            return false;
        }
    }
}
