<?php

namespace App\Http\Middleware;

use App\Services\LoginHistoryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackApiSessionActivity
{
    public function __construct(private readonly LoginHistoryService $history)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('api/auth/logout')) {
            return $response;
        }

        $user = $request->user();
        if (!$user) {
            return $response;
        }

        $deviceUuid = null;

        $accessToken = $user->currentAccessToken();
        $tokenName = (string) ($accessToken?->name ?? '');
        if (str_starts_with($tokenName, 'mobile-app:')) {
            $deviceUuid = strtolower(trim(substr($tokenName, strlen('mobile-app:'))));
        }

        if (!$deviceUuid) {
            $deviceUuid = strtolower(trim((string) $request->input('device_uuid', '')));
        }

        if ($deviceUuid !== '') {
            $this->history->startOrTouchApiSession($user, $request, $deviceUuid, [
                'platform' => (string) $request->header('X-Client-Platform', ''),
                'device_name' => (string) $request->header('X-Client-Device', ''),
                'app_version' => (string) $request->header('X-App-Version', ''),
            ]);
        }

        return $response;
    }
}
