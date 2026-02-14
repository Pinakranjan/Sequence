<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        $minutes = $this->otpTtlMinutes();
        $code = (string) random_int(100000, 999999);

        Cache::put($this->cacheKey($email), $code, now()->addMinutes($minutes));

        $this->sendOtpEmail($email, [
            'title' => 'Verification Code',
            'name' => $user?->name,
            'code' => $code,
            'minutes' => $minutes,
        ]);

        return response()->json([
            'message' => 'An OTP code has been sent to your email. Please check and confirm.',
            'otp_expiration' => $minutes * 60,
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'min:4', 'max:15'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $submittedOtp = trim($validated['otp']);

        $cachedOtp = Cache::get($this->cacheKey($email));

        if (!$cachedOtp) {
            return response()->json([
                'message' => 'The verification OTP has expired. Please resend and try again.',
            ], 400);
        }

        if (!hash_equals((string) $cachedOtp, (string) $submittedOtp)) {
            return response()->json([
                'message' => 'Invalid OTP.',
            ], 400);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 400);
        }

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        Cache::forget($this->cacheKey($email));

        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }

    private function otpTtlMinutes(): int
    {
        $minutes = (int) env('OTP_VISIBILITY_TIME', 3);
        return $minutes > 0 ? $minutes : 3;
    }

    private function cacheKey(string $email): string
    {
        return 'otp:' . $email;
    }

    private function sendOtpEmail(string $email, array $data): void
    {
        try {
            // Send only if mail is configured. If not, keep flow working (OTP is still returned via UI timer).
            if (!config('mail.default') || !config('mail.from.address')) {
                return;
            }

            Mail::send('email.verification_code', $data, function ($message) use ($email) {
                $message->to($email)->subject('Verification Code');
            });
        } catch (\Throwable $e) {
            // Intentionally swallow errors to avoid blocking UI; mail config may be incomplete in local env.
        }
    }
}
