<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Utility\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;
use App\Mail\VerificationCodeMail;

class ApiAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/validate-email",
     *     summary="Validate email and return user preview info",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="photo_url", type="string", nullable=true),
     *                 @OA\Property(property="has_pin_enabled", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or user not found"
     *     )
     * )
     */
    public function validateEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'We could not find an account with that email address.',
            ], 422);
        }

        // Check user/business status
        $statusError = $this->getUserOrBusinessStatusError($user);
        if ($statusError) {
            return response()->json([
                'success' => false,
                'message' => $statusError,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
                'photo_url' => $user->photo_url,
                'has_pin_enabled' => (bool) ($user->pin && $user->pin_enabled),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Login with email + password OR pin",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="auth_method", type="string", enum={"password", "pin"}, example="password"),
     *             @OA\Property(property="password", type="string", example="password"),
     *             @OA\Property(property="pin", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="photo_url", type="string", nullable=true),
     *                 @OA\Property(property="has_pin_enabled", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or invalid credentials"
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'auth_method' => ['sometimes', 'string', 'in:password,pin'],
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 422);
        }

        // Re-check status
        $statusError = $this->getUserOrBusinessStatusError($user);
        if ($statusError) {
            return response()->json([
                'success' => false,
                'message' => $statusError,
            ], 422);
        }

        $authMethod = $request->input('auth_method', 'password');

        if ($authMethod === 'pin') {
            $request->validate(['pin' => ['required', 'digits:4']]);

            if (!$user->pin || !$user->pin_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN login is not available for this account.',
                ], 422);
            }

            if (!Hash::check($request->pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid PIN. Please try again.',
                ], 422);
            }
        } else {
            $request->validate(['password' => ['required', 'string']]);

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password. Please try again.',
                ], 422);
            }
        }

        // Revoke existing mobile tokens
        $user->tokens()->where('name', 'mobile-app')->delete();

        // Create new token
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
                'photo_url' => $user->photo_url,
                'has_pin_enabled' => (bool) ($user->pin && $user->pin_enabled),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Register a new user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "company_code"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *             @OA\Property(property="password_confirmation", type="string", example="password"),
     *             @OA\Property(property="company_code", type="string", example="ABC1234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="photo_url", type="string", nullable=true),
     *                 @OA\Property(property="has_pin_enabled", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or business code issues"
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'company_code' => ['required', 'string', 'size:10'],
        ]);

        $code = strtoupper(trim($validated['company_code']));

        // Check super admin codes
        $superCodes = [
            (string) config('services.business.super_admin_code_1'),
            (string) config('services.business.super_admin_code_2'),
        ];
        $isSuperAdmin = in_array($code, array_filter($superCodes), true);

        $role = 'user';
        $companyId = null;

        if (!$isSuperAdmin) {
            $business = Business::where('company_code', $code)->first();
            if (!$business) {
                return response()->json(['success' => false, 'message' => 'Invalid business code.'], 422);
            }
            if ((int) ($business->is_locked ?? 0) === 1) {
                return response()->json(['success' => false, 'message' => 'This business is locked. Please contact the administrator.'], 422);
            }
            if ((int) ($business->is_active ?? 0) !== 1) {
                return response()->json(['success' => false, 'message' => 'This business is inactive. Please contact the administrator.'], 422);
            }
            $approved = (int) ($business->approved_users ?? 0);
            if ($approved > 0) {
                $current = User::where('company_id', $business->id)->count();
                if ($current >= $approved) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This business has reached its approved users limit (' . $current . '/' . $approved . ').',
                    ], 422);
                }
            }
            $companyId = $business->id;
        } else {
            $role = 'super admin';
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'company_id' => $companyId,
            'status' => '1',
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'photo_url' => $user->photo_url,
                'has_pin_enabled' => false,
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/validate-business-code",
     *     summary="Validate business code (AJAX)",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"company_code"},
     *             @OA\Property(property="company_code", type="string", example="ABC1234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code validated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=true),
     *             @OA\Property(property="type", type="string", example="business"),
     *             @OA\Property(property="business", type="object",
     *                 @OA\Property(property="name", type="string")
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid business code"
     *     )
     * )
     */
    public function validateBusinessCode(Request $request): JsonResponse
    {
        $request->validate([
            'company_code' => ['required', 'string', 'size:10'],
        ]);

        $code = strtoupper(trim((string) $request->input('company_code')));

        $superCodes = [
            config('services.business.super_admin_code_1'),
            config('services.business.super_admin_code_2'),
        ];
        if (in_array($code, array_filter($superCodes), true)) {
            return response()->json([
                'valid' => true,
                'type' => 'super_admin',
                'message' => 'Super admin code accepted.',
            ]);
        }

        $business = Business::where('company_code', $code)->first();
        if (!$business) {
            return response()->json(['valid' => false, 'message' => 'Invalid business code.'], 422);
        }
        if ((int) ($business->is_locked ?? 0) === 1) {
            return response()->json(['valid' => false, 'message' => 'This business is locked.'], 422);
        }
        if ((int) ($business->is_active ?? 0) !== 1) {
            return response()->json(['valid' => false, 'message' => 'This business is inactive.'], 422);
        }

        $approved = (int) ($business->approved_users ?? 0);
        $current = User::where('company_id', $business->id)->count();
        if ($approved > 0 && $current >= $approved) {
            return response()->json([
                'valid' => false,
                'message' => 'Users limit reached (' . $current . '/' . $approved . ').',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'type' => 'business',
            'business' => ['name' => $business->name],
            'message' => 'Business code accepted.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/forgot-password",
     *     summary="Send password reset code",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reset code sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="User not found"
     *     )
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'We could not find an account with that email address.',
            ], 422);
        }

        // Generate and cache verification code
        $code = rand(100000, 999999);
        Cache::put('password_reset_code_' . $email, $code, now()->addMinutes(15));

        try {
            Mail::to($email)->send(new VerificationCodeMail($code));
        } catch (\Throwable $e) {
            // Continue in dev
        }

        return response()->json([
            'success' => true,
            'message' => 'Reset code sent to your email.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Logout and revoke token",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/user",
     *     summary="Get authenticated user info",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="photo_url", type="string", nullable=true),
     *                 @OA\Property(property="has_pin_enabled", type="boolean")
     *             )
     *         )
     *     )
     * )
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
                'photo_url' => $user->photo_url,
                'has_pin_enabled' => (bool) ($user->pin && $user->pin_enabled),
            ],
        ]);
    }

    /**
     * Check user/business status for errors.
     */
    protected function getUserOrBusinessStatusError(User $user): ?string
    {
        $userLocked = (int) ($user->is_locked ?? 0) === 1;
        $userActive = (string) ($user->status ?? '0') === '1';

        if ($userLocked) {
            return 'Your account is locked. Please contact the administrator.';
        }
        if (!$userActive) {
            return 'Your account is inactive. Please contact the administrator.';
        }

        if ($user->company_id) {
            $business = Business::find($user->company_id);
            if ($business) {
                if ((int) ($business->is_locked ?? 0) === 1) {
                    return 'Your business is locked. Please contact the administrator.';
                }
                if ((int) ($business->is_active ?? 0) !== 1) {
                    return 'Your business is inactive. Please contact the administrator.';
                }
            } else {
                return 'Your business record is missing. Please contact the administrator.';
            }
        }

        return null;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/unlock",
     *     summary="Unlock session (verify credentials)",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"auth_method"},
     *             @OA\Property(property="auth_method", type="string", enum={"password", "pin"}, example="pin"),
     *             @OA\Property(property="password", type="string", example="password"),
     *             @OA\Property(property="pin", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session unlocked",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid credentials or PIN disabled"
     *     )
     * )
     */
    public function unlock(Request $request): JsonResponse
    {
        $request->validate([
            'auth_method' => ['required', 'string', 'in:password,pin'],
        ]);

        $user = $request->user();
        $authMethod = $request->input('auth_method');

        if ($authMethod === 'pin') {
            $request->validate(['pin' => ['required', 'digits:4']]);

            if (!$user->pin || !$user->pin_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN login is not available for this account.',
                ], 422);
            }

            if (!Hash::check($request->pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid PIN. Please try again.',
                ], 422);
            }
        } else {
            $request->validate(['password' => ['required', 'string']]);

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password. Please try again.',
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Session unlocked successfully.',
        ]);
    }
}
