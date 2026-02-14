<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     * Only allow users whose role is 'super admin' (case-insensitive variants).
     * Returns 403 for unauthorized users.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user) {
            // Let auth middleware handle unauthenticated requests; redirect to login
            return redirect()->route('login');
        }

        $role = strtolower(trim((string) optional($user)->role));
        $allowed = [
            'super admin', 'admin'
        ];
        $isSuper = in_array($role, $allowed, true);

        if (! $isSuper) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
