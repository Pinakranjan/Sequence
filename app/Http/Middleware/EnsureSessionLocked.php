<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionLocked
{
    /**
     * Redirect all web requests to lock screen if a locked session exists.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow lock/unlock endpoints and public assets to pass through
        if (
            $request->routeIs('lock.show') ||
            $request->routeIs('lock.unlock') ||
            $request->routeIs('lock.logout') ||
            $request->routeIs('auth.check') ||
            $request->routeIs('login') ||
            $request->routeIs('admin.login')
        ) {
            return $next($request);
        }

        // Static assets and storage
        if ($request->is('backend/*') || $request->is('build/*') || $request->is('storage/*') || $request->is('favicon.ico')) {
            return $next($request);
        }

        if ($request->session()->has('locked_user')) {
            return redirect()->route('lock.show');
        }

        return $next($request);
    }
}
