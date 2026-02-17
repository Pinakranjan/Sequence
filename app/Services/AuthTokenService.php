<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthTokenService
{
    public function __construct(private readonly LoginHistoryService $loginHistoryService)
    {
    }

    public function issueTokenPair(User $user, Request $request, array $payload, bool $revokeAllExisting = false): array
    {
        $deviceUuid = strtolower(trim((string) ($payload['device_uuid'] ?? '')));

        return DB::transaction(function () use ($user, $request, $payload, $deviceUuid, $revokeAllExisting) {
            if ($revokeAllExisting) {
                $this->revokeAll($user);
            }

            $device = UserDevice::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_uuid' => $deviceUuid,
                ],
                [
                    'platform' => $payload['platform'] ?? null,
                    'device_name' => $payload['device_name'] ?? null,
                    'app_version' => $payload['app_version'] ?? null,
                    'last_ip' => $request->ip(),
                    'last_user_agent' => $request->userAgent(),
                    'last_seen_at' => now(),
                ]
            );

            $tokenName = 'mobile-app:' . $device->device_uuid;
            $user->tokens()->where('name', $tokenName)->delete();

            $accessExpiresAt = now()->addMinutes((int) config('auth_tokens.access_ttl_minutes', 60));
            $accessToken = $user->createToken($tokenName, ['*'], $accessExpiresAt)->plainTextToken;

            $plainRefreshToken = bin2hex(random_bytes(64));
            $refreshToken = RefreshToken::create([
                'jti' => (string) Str::uuid(),
                'user_id' => $user->id,
                'user_device_id' => $device->id,
                'token_hash' => hash('sha256', $plainRefreshToken),
                'expires_at' => now()->addDays((int) config('auth_tokens.refresh_ttl_days', 7)),
                'last_ip' => $request->ip(),
                'last_user_agent' => $request->userAgent(),
            ]);

            $this->loginHistoryService->startOrTouchApiSession($user, $request, $device->device_uuid, [
                'platform' => $payload['platform'] ?? null,
                'device_name' => $payload['device_name'] ?? null,
                'app_version' => $payload['app_version'] ?? null,
            ]);

            return [
                'token_type' => 'Bearer',
                'access_token' => $accessToken,
                'access_token_expires_at' => $accessExpiresAt->toIso8601String(),
                'refresh_token' => $plainRefreshToken,
                'refresh_token_expires_at' => $refreshToken->expires_at?->toIso8601String(),
                'device_uuid' => $device->device_uuid,
            ];
        });
    }

    public function rotateRefreshToken(Request $request, string $deviceUuid, string $plainRefreshToken): ?array
    {
        $tokenHash = hash('sha256', $plainRefreshToken);

        return DB::transaction(function () use ($request, $deviceUuid, $tokenHash) {
            $refreshToken = RefreshToken::with(['user', 'device'])
                ->where('token_hash', $tokenHash)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (!$refreshToken || !$refreshToken->device || $refreshToken->device->device_uuid !== $deviceUuid) {
                return null;
            }

            $refreshToken->update([
                'revoked_at' => now(),
                'last_used_at' => now(),
                'last_ip' => $request->ip(),
                'last_user_agent' => $request->userAgent(),
            ]);

            $user = $refreshToken->user;
            if (!$user) {
                return null;
            }

            $payload = [
                'device_uuid' => $refreshToken->device->device_uuid,
                'platform' => $refreshToken->device->platform,
                'device_name' => $refreshToken->device->device_name,
                'app_version' => $refreshToken->device->app_version,
            ];

            $issued = $this->issueTokenPair($user, $request, $payload, false);

            RefreshToken::where('token_hash', hash('sha256', $issued['refresh_token']))
                ->update(['rotated_from_id' => $refreshToken->id]);

            return $issued;
        });
    }

    public function revokeAll(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            RefreshToken::where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                    'last_used_at' => now(),
                ]);

            $this->loginHistoryService->terminateActiveApiSessionsByUser((int) $user->id, 'REVOKED');

            UserDevice::where('user_id', $user->id)->delete();
        });
    }

    public function revokeDeviceSession(User $user, string $deviceUuid, string $reason = 'SESSION.REVOKED'): void
    {
        $deviceUuid = strtolower(trim($deviceUuid));
        if ($deviceUuid === '') {
            return;
        }

        DB::transaction(function () use ($user, $deviceUuid, $reason) {
            $tokenName = 'mobile-app:' . $deviceUuid;
            $user->tokens()->where('name', $tokenName)->delete();

            $device = UserDevice::query()
                ->where('user_id', $user->id)
                ->where('device_uuid', $deviceUuid)
                ->first();

            if ($device) {
                RefreshToken::where('user_id', $user->id)
                    ->where('user_device_id', $device->id)
                    ->whereNull('revoked_at')
                    ->update([
                        'revoked_at' => now(),
                        'last_used_at' => now(),
                    ]);

                $device->delete();
            }

            $this->loginHistoryService->endApiSessionByDevice($user, $deviceUuid, $reason);
        });
    }
}
