<?php

namespace App\Http\Middleware;

use App\Services\LoginHistoryService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Utility\UserLoginRegister;

class TrackUserSessionActivity
{
    public function __construct(private readonly LoginHistoryService $history)
    {
    }

    /**
     * Update last_connected_time on each authenticated request and terminate if this session is ended.
     */
    public function handle(Request $request, Closure $next)
    {
        // If the session is explicitly locked (lock screen), do not mutate login history here.
        // Access is already gated by EnsureSessionLocked middleware.
        if ($request->session()->has('locked_user')) {
            return $next($request);
        }

        // If user is not authenticated but there is an open session row for this session_id,
        // mark it as SESSION.EXPIRED (e.g., Breeze session timeout)
        if (!Auth::check()) {
            try {
                $sid = $request->session()->getId();
                if ($sid) {
                    $row = UserLoginRegister::where('session_id', $sid)
                        ->whereNull('session_end_time')
                        ->orderByDesc('id')
                        ->first();
                    if ($row) {
                        $row->session_end_time = now();
                        $row->session_end_type = 'SESSION.EXPIRED';
                        $row->save();
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Only track for authenticated web users
        if (Auth::check()) {
            $user = Auth::user();
            $active = $this->history->touchOrCheck($user, $request);
            if (!$active) {
                // Session has been marked ended (EXPIRED/TERMINATED/LOGOUT) or no matching history
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with([
                    'message' => 'Your session has ended (logged in from another browser or expired). Please login again.',
                    'alert-type' => 'error',
                    'positionClass' => 'toast-bottom-right',
                ]);
            }
        }

        return $next($request);
    }
}
