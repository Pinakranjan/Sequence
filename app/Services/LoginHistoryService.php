<?php

namespace App\Services;

use App\Models\User;
use App\Models\Utility\UserLoginRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoginHistoryService
{
    /**
     * Start a new login session record for the given user and current session.
     * - Expires any previous open sessions for this user (session_end_type='EXPIRED').
     * - Creates a new row with current session_id and metadata.
     */
    public function startSession(User $user, Request $request): void
    {
        $sessionId = $request->session()->getId();
        $systemName = gethostname() ?: php_uname('n');
        $agent = (string) $request->header('User-Agent', '');
        if ($agent) {
            $systemName = $systemName ? ($systemName . ' | ' . $agent) : $agent;
        }

        DB::beginTransaction();
        try {
            // End any previous open sessions for this user as FRESH LOGIN
            UserLoginRegister::where('user_id', $user->id)
                ->whereNull('session_end_time')
                ->update([
                    'session_end_time' => now(),
                    'session_end_type' => 'FRESH LOGIN',
                ]);

            // Create new session record
            $row = new UserLoginRegister();
            $row->user_id = $user->id;
            $row->company_id = $user->company_id ?? null;
            $row->session_id = $sessionId;
            $row->system_name = $systemName;
            $row->login_time = now();
            $row->last_connected_time = now();
            // session_end_time/type left NULL for active session
            $row->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            // Do not block login on logging failure; optionally log error
            try { Log::error('LoginHistory startSession failed: ' . $e->getMessage()); } catch (Throwable $e2) {}
        }
    }

    /**
     * End the current session for a user, marking type (LOGOUT/TERMINATED/...)
     */
    public function endSession(User $user, Request $request, string $type = 'LOGGED OUT'): void
    {
        $sessionId = $request->session()->getId();
        try {
            $row = UserLoginRegister::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->whereNull('session_end_time')
                ->orderByDesc('id')
                ->first();
            if ($row) {
                $row->session_end_time = now();
                $row->session_end_type = strtoupper($type);
                $row->logout_time = now()->toDateTimeString();
                $row->save();
            }
        } catch (Throwable $e) {
            try { Log::error('LoginHistory endSession failed: ' . $e->getMessage()); } catch (Throwable $e2) {}
        }
    }

    /**
     * Touch last_connected_time for current session; return false if this session is no longer active.
     */
    public function touchOrCheck(User $user, Request $request): bool
    {
        $sessionId = $request->session()->getId();
        try {
            $row = UserLoginRegister::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->orderByDesc('id')
                ->first();
            if (!$row) return false; // no matching history -> treat as invalid session

            if ($row->session_end_time !== null) {
                return false; // already ended -> force logout
            }

            $row->last_connected_time = now();
            $row->save();
            return true;
        } catch (Throwable $e) {
            try { Log::error('LoginHistory touch failed: ' . $e->getMessage()); } catch (Throwable $e2) {}
            return true; // do not block request on logging error
        }
    }
    /**
     * Terminate all active (open) sessions associated with a business id.
     * Marks session_end_time and session_end_type; also sets logout_time string.
     */
    public function terminateSessionsByBusiness(int $businessId, string $type = 'TERMINATED'): void
    {
        try {
            UserLoginRegister::where('company_id', $businessId)
                ->whereNull('session_end_time')
                ->update([
                    'session_end_time' => now(),
                    'session_end_type' => strtoupper($type),
                    'logout_time' => now()->toDateTimeString(),
                ]);
        } catch (Throwable $e) {
            try { Log::error('terminateSessionsByBusiness failed: ' . $e->getMessage()); } catch (Throwable $e2) {}
        }
    }
}
