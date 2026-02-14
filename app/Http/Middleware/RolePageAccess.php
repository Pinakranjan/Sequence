<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolePageAccess
{
    /**
     * Enforce role-based page access defined in config('services.role_pages').
     * Apply this middleware only to routes that should be protected by role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        // If not authenticated, let 'auth' middleware handle it elsewhere.
        if (!$user) {
            return abort(403, 'Unauthorized.');
        }

        $route = $request->route();
        $name = $route ? $route->getName() : null;
        if (!$name) {
            return abort(403, 'Unauthorized.');
        }

        $role = strtolower(trim((string)($user->role ?? '')));
        $map = (array) config('services.role_pages', []);
        $allowed = (array) ($map[$role] ?? []);

        if (!in_array($name, $allowed, true)) {
            // Block direct URL access
            return abort(403, 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
