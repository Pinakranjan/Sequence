<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Services\LoginHistoryService;
use App\Models\Utility\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    public function __construct(private readonly LoginHistoryService $history)
    {
    }
    public function AdminLogout(Request $request)
    {
        // Mark current session as logged out in history (best-effort)
        try {
            if (Auth::check()) {
                $this->history->endSession(Auth::user(), $request, 'LOGGED OUT');
            }
        } catch (\Throwable $e) {
        }
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function AdminLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $email = strtolower((string) ($credentials['email'] ?? ''));

        // Pre-validate user and business status to provide clear errors
        $user = User::where('email', $email)->first();
        if ($user) {
            $statusError = $this->getUserOrBusinessStatusError($user);
            if ($statusError) {
                return redirect()->back()->withErrors(['email' => $statusError])->withInput();
            }
        }

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            $verificationCode = rand(100000, 999999);
            session(['verification_code' => $verificationCode, 'user_id' => $user->id]);

            Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));
            Auth::logout();

            return redirect()->route('custom.verification.form')->with('status', 'Verification code sent to your email. Please check your inbox.');
        }

        return redirect()->back()->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    public function ShowVerification()
    {
        return view('auth.verify');
    }

    public function VerificationVerify(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        if ($request->code == session('verification_code')) {
            // Handle pending registration flow
            if ($request->session()->has('pending_registration')) {
                $data = $request->session()->get('pending_registration');

                // Avoid duplicate accounts if email was registered meanwhile
                $existing = User::where('email', $data['email'])->first();
                if ($existing) {
                    $request->session()->forget(['verification_code', 'pending_registration']);
                    return redirect()->route('login')->withErrors(['email' => 'This email is already registered. Please log in.']);
                }

                // Create user and log in (apply business/role)
                $user = new User();
                $user->name = $data['name'];
                $user->email = $data['email'];
                $user->password = $data['password']; // already hashed

                // Finalize role/business assignment with a last validation
                $code = strtoupper((string) ($data['company_code'] ?? ''));
                $superCodes = [
                    (string) config('services.business.super_admin_code_1'),
                    (string) config('services.business.super_admin_code_2'),
                ];

                if ($code && in_array($code, array_filter($superCodes), true)) {
                    $user->role = 'super admin';
                    $user->company_id = null;
                } elseif ($code) {
                    $business = Business::where('company_code', $code)->first();
                    if (!$business) {
                        $request->session()->forget(['verification_code', 'pending_registration']);
                        return redirect()->route('register')->withErrors(['company_code' => 'Invalid business code. Please register again.']);
                    }
                    if ((int) ($business->is_locked ?? 0) === 1 || (int) ($business->is_active ?? 0) !== 1) {
                        $request->session()->forget(['verification_code', 'pending_registration']);
                        return redirect()->route('register')->withErrors(['company_code' => 'Business is not eligible for registration. Please contact administrator.']);
                    }
                    $approved = (int) ($business->approved_users ?? 0);
                    if ($approved > 0) {
                        $current = User::where('company_id', $business->id)->count();
                        if ($current >= $approved) {
                            $request->session()->forget(['verification_code', 'pending_registration']);
                            return redirect()->route('register')->withErrors([
                                'company_code' => 'This business has reached its approved users limit (' . $current . '/' . $approved . ').',
                            ]);
                        }
                    }
                    $user->role = 'user';
                    $user->company_id = $business->id;
                }

                $user->save();

                Auth::login($user);
                // Start login history for this session
                try {
                    $this->history->startSession($user, $request);
                } catch (\Throwable $e) {
                }
                $request->session()->forget(['verification_code', 'pending_registration']);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Registration verified and account created',
                        'redirect' => url('/')
                    ]);
                }
                return redirect()->intended('/');
            }

            // Handle login verification flow
            $user = User::find(session('user_id'));

            if ($user) {
                // Re-validate current status (business/account) before login
                $statusError = $this->getUserOrBusinessStatusError($user);
                if ($statusError) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $statusError
                        ], 422);
                    }
                    return redirect()->route('login')->withErrors(['email' => $statusError]);
                }

                Auth::login($user);
                // Start login history for this session
                try {
                    $this->history->startSession($user, $request);
                } catch (\Throwable $e) {
                }
                $request->session()->forget(['verification_code', 'user_id']);

                // Check if it's an AJAX request
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Verification successful',
                        'redirect' => url('/')
                    ]);
                }

                return redirect()->intended('/');
            } else {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found. Please login again.'
                    ], 404);
                }
                return redirect()->route('login')->withErrors(['email' => 'User not found. Please login again.']);
            }
        } else {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code. Please try again.'
                ], 422);
            }
            return redirect()->back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }
    }

    // Registration with verification (defer persistence until code verified)
    public function RegisterStart(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
            'company_code' => ['required', 'string', 'size:10'],
        ]);

        // Normalize and validate business code here as well
        $code = strtoupper(trim($validated['company_code']));
        $superCodes = [
            (string) config('services.business.super_admin_code_1'),
            (string) config('services.business.super_admin_code_2'),
        ];
        $isSuperAdmin = in_array($code, array_filter($superCodes), true);

        $pendingRole = 'user';
        $pendingBusinessId = null;

        if (!$isSuperAdmin) {
            $business = Business::where('company_code', $code)->first();
            if (!$business) {
                return back()->withErrors(['company_code' => 'Invalid business code.'])->withInput();
            }
            if ((int) ($business->is_locked ?? 0) === 1) {
                return back()->withErrors(['company_code' => 'This business is locked. Please contact the administrator.'])->withInput();
            }
            if ((int) ($business->is_active ?? 0) !== 1) {
                return back()->withErrors(['company_code' => 'This business is inactive. Please contact the administrator.'])->withInput();
            }
            $approved = (int) ($business->approved_users ?? 0);
            if ($approved > 0) {
                $current = User::where('company_id', $business->id)->count();
                if ($current >= $approved) {
                    return back()->withErrors([
                        'company_code' => 'This business has reached its approved users limit (' . $current . '/' . $approved . ').',
                    ])->withInput();
                }
            }
            $pendingBusinessId = $business->id;
        } else {
            $pendingRole = 'super admin';
        }

        // Store pending registration (hash password now)
        $pending = [
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $pendingRole,
            'company_id' => $pendingBusinessId,
            'company_code' => $code,
        ];
        session(['pending_registration' => $pending]);

        // Generate and send verification code
        $verificationCode = rand(100000, 999999);
        session(['verification_code' => $verificationCode]);

        try {
            Mail::to($pending['email'])->send(new VerificationCodeMail($verificationCode));
        } catch (\Throwable $e) {
            // In dev, continue; otherwise you could log error
        }

        // Dev helper: no flash here to avoid expiring the key before POST; view reads session('verification_code') directly

        return redirect()->route('custom.verification.form')->with('status', 'Verification code sent to your email. Please check your inbox.');
    }

    /**
     * Validate provided business code before proceeding with registration (AJAX)
     */
    public function validateBusinessCode(Request $request)
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
                'message' => 'Super admin code accepted. You will be registered without business.',
            ]);
        }

        $business = Business::where('company_code', $code)->first();
        if (!$business) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid business code.',
            ], 422);
        }
        if ((int) ($business->is_locked ?? 0) === 1) {
            return response()->json([
                'valid' => false,
                'message' => 'This business is locked. Please contact the administrator.'
            ], 423);
        }
        if ((int) ($business->is_active ?? 0) !== 1) {
            return response()->json([
                'valid' => false,
                'message' => 'This business is inactive. Please contact the administrator.'
            ], 422);
        }

        $approved = (int) ($business->approved_users ?? 0);
        $current = User::where('company_id', $business->id)->count();
        if ($approved > 0 && $current >= $approved) {
            return response()->json([
                'valid' => false,
                'message' => 'This business has reached its approved users limit (' . $current . '/' . $approved . ').',
                'approved_users' => $approved,
                'consumed_users' => $current,
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'type' => 'business',
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'approved_users' => (int) $business->approved_users,
                'current_users' => $current,
            ],
            'message' => 'Business code accepted.'
        ]);
    }

    // Forgot Password Flow (email + verification code)
    public function showForgotPasswordForm()
    {
        return view('auth.forgot');
    }

    public function forgotEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email']
        ]);

        $email = strtolower($data['email']);
        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'We could not find a user with that email address.']);
        }

        // Enforce the same checks here as login
        $statusError = $this->getUserOrBusinessStatusError($user);
        if ($statusError) {
            return back()->withErrors(['email' => $statusError])->withInput();
        }

        // Remember email for next step
        session(['pending_password_reset_email' => $email]);

        return redirect()->route('password.code.password.form')->with('status', 'Email verified. Please set your new password.');
    }

    public function showForgotPasswordNewForm(Request $request)
    {
        if (!session()->has('pending_password_reset_email')) {
            return redirect()->route('password.code.request');
        }
        return view('auth.forgot_password_password');
    }

    public function sendResetCode(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $email = session('pending_password_reset_email') ?? strtolower((string) $request->input('email'));
        if (!$email) {
            return redirect()->route('password.code.request');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'We could not find a user with that email address.']);
        }

        // Block reset for inactive/locked accounts or businesses
        $statusError = $this->getUserOrBusinessStatusError($user);
        if ($statusError) {
            return back()->withErrors(['email' => $statusError])->withInput();
        }

        $code = rand(100000, 999999);
        $key = 'password_reset_code:' . $email;
        Cache::put($key, $code, now()->addMinutes(10));

        // Stash pending password reset (hashed) until code is verified
        session([
            'pending_password_reset' => [
                'email' => $email,
                'password' => Hash::make($validated['password']),
            ]
        ]);

        // Reuse existing mailable
        Mail::to($user->email)->send(new VerificationCodeMail($code));

        // Optionally surface the code in-session for the view to display
        if (config('app.show_dev_codes')) {
            session()->flash('password_reset_code', $code);
        }

        return redirect()->route('password.code.form', ['email' => $email])
            ->with('status', 'We\'ve emailed you a 6-digit verification code. It expires in 10 minutes.');
    }

    public function showResetPasswordForm(Request $request)
    {
        if (!$request->has('email')) {
            return redirect()->route('password.forgot');
        }
        return view('auth.reset');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = strtolower($request->email);
        $key = 'password_reset_code:' . $email;
        $stored = Cache::get($key);
        if (!$stored || (string) $stored !== (string) $request->code) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification code.'
                ], 422);
            }
            return back()->withErrors(['code' => 'Invalid or expired verification code.'])->withInput();
        }

        // Fetch pending password reset data
        $pending = session('pending_password_reset');
        if (!$pending || ($pending['email'] ?? null) !== $email) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your reset session has expired. Please start again.'
                ], 422);
            }
            return back()->withErrors(['email' => 'Your reset session has expired. Please start again.'])->withInput();
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'
                ], 404);
            }
            return back()->withErrors(['email' => 'User not found.'])->withInput();
        }

        // Block reset for inactive/locked accounts or businesses
        $statusError = $this->getUserOrBusinessStatusError($user);
        if ($statusError) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $statusError
                ], 422);
            }
            return back()->withErrors(['email' => $statusError])->withInput();
        }

        // Apply the pending hashed password and set last modified by (self-reset)
        $user->password = $pending['password'];
        $user->lastmodified_by = $user->name; // resetting own password
        $user->save();
        Cache::forget($key);
        session()->forget('pending_password_reset');

        // Authenticate user
        Auth::login($user);
        // Start login history session after password reset
        try {
            $this->history->startSession($user, $request);
        } catch (\Throwable $e) {
        }

        // If AJAX, return JSON so client can show success animation then redirect
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your password has been reset successfully.',
                'redirect' => url('/')
            ]);
        }

        // Non-AJAX: proceed to dashboard
        return redirect()->intended('/')->with([
            'message' => 'Your password has been reset successfully.',
            'alert-type' => 'success',
            'timeout' => 4000,
        ]);
    }

    public function clearCache(Request $request)
    {
        try {
            Artisan::call('optimize:clear');
            // Optional: warm up minimal caches
            // Artisan::call('config:cache');
        } catch (\Throwable $e) {
            return back()->with([
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
                'alert-type' => 'error',
            ]);
        }

        return back()->with([
            'message' => 'Application cache cleared successfully.',
            'alert-type' => 'success',
        ]);
    }

    // Lock Screen
    public function showLockScreen(Request $request)
    {
        // If not yet marked locked and user is authenticated, capture minimal info
        if (!$request->session()->has('locked_user') && Auth::check()) {
            $user = Auth::user();
            $request->session()->put('locked_user', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo' => $user->photo ?? null,
            ]);
            // NOTE: Do NOT logout here. We keep the auth session alive and gate access via middleware.
        }

        if (!$request->session()->has('locked_user')) {
            return redirect()->route('login');
        }

        $locked = $request->session()->get('locked_user');
        return view('auth.lock', ['locked' => $locked]);
    }

    public function unlock(Request $request)
    {
        $authMethod = $request->input('auth_method', 'password');

        if ($authMethod === 'pin') {
            $request->validate([
                'pin' => ['required', 'string', 'digits:4'],
            ]);
        } else {
            $request->validate([
                'password' => ['required', 'string'],
            ]);
        }

        $locked = $request->session()->get('locked_user');
        if (!$locked || !isset($locked['id'])) {
            return redirect()->route('login');
        }

        $user = User::find($locked['id']);
        if (!$user) {
            $request->session()->forget('locked_user');
            return redirect()->route('login');
        }

        $authMethod = $request->input('auth_method', 'password');

        if ($authMethod === 'pin') {
            if (!$user->pin_enabled) {
                $msg = 'PIN authentication is disabled for this account.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withErrors(['pin' => $msg]);
            }
            if (!Hash::check($request->pin, $user->pin)) {
                $msg = 'Incorrect PIN. Please try again.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withErrors(['pin' => $msg]);
            }
        } else {
            if (!Hash::check($request->password, $user->password)) {
                $msg = 'Incorrect password. Please try again.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withErrors(['password' => $msg]);
            }
        }

        // Auth session should still be active; only verify password and clear lock state
        try {
            // Lightly touch the session row; do not create a new session here
            app(LoginHistoryService::class)->touchOrCheck($user, $request);
        } catch (\Throwable $e) {
        }
        $request->session()->forget('locked_user');

        // Use role-based redirect for consistent behavior with initial login
        $redirectUrl = $this->getRoleBasedRedirect($user);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Welcome back, your session is unlocked.',
                'redirect' => $redirectUrl
            ]);
        }

        return redirect()->intended($redirectUrl)->with([
            'message' => 'Welcome back, your session is unlocked.',
            'alert-type' => 'success',
            'timeout' => 3000,
        ]);
    }

    public function lockLogout(Request $request)
    {
        // Clear lock state and return to login to allow different user sign-in
        // Attempt to end the session in history if we can identify the user
        try {
            $locked = $request->session()->get('locked_user');
            if (isset($locked['id'])) {
                $u = User::find($locked['id']);
                if ($u) {
                    app(LoginHistoryService::class)->endSession($u, $request, 'LOGGED OUT');
                }
            }
        } catch (\Throwable $e) {
        }
        $request->session()->forget('locked_user');
        // Also ensure any auth guard is logged out
        try {
            Auth::logout();
        } catch (\Throwable $e) {
        }
        return redirect()->route('home');
    }

    public function AdminProfile()
    {
        $id = Auth::user()->id;
        $profileData = User::find($id);

        return view('admin.admin_profile', compact('profileData'));
    }

    public function ProfileStore(Request $request)
    {
        // Accept wrapped value payloads (e.g., { value: { ... } } or value as JSON string)
        $value = $request->input('value');
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }
        if (is_array($value)) {
            $request->merge($value);
        }

        $user = User::findOrFail(Auth::id());
        if (!$user) {
            return redirect()->route('login');
        }

        // Normalize some inputs
        $request->merge([
            'name' => trim((string) $request->input('name')),
            // keep only digits for phone and trim to 10 if longer
            'phone' => substr(preg_replace('/\D+/', '', (string) $request->input('phone')), 0, 10),
            'address' => trim((string) $request->input('address')),
        ]);

        // Validate
        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'digits:10'],
                'address' => ['required', 'string', 'max:500'],
                'photo' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            ],
            [
                'photo.uploaded' => 'Profile photo must not be larger than 2 MB.',
                'photo.max' => 'Profile photo must not be larger than 2 MB.',
            ]
        );

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please fix the error(s).',
                    'errors' => $validator->errors(),
                    'alert-type' => 'error',
                    'positionClass' => 'toast-bottom-right',
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Prepare new values but don't persist yet
        $user->fill([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);
        // Track who modified the profile
        $user->lastmodified_by = optional(Auth::user())->name;

        $imageChanged = $request->hasFile('photo');

        // If nothing changed and no new image provided
        if (!$user->isDirty() && !$imageChanged) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes were made to your profile.',
                    'alert-type' => 'warning',
                    'positionClass' => 'toast-bottom-right',
                ], 422);
            }
            return redirect()->back()->with([
                'success' => false,
                'message' => 'No changes were made to your profile.',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ]);
        }

        $oldPhotoPath = $user->photo; // stores only filename in current app
        $newPhotoName = null; // defer deletion of old photo until after successful save

        try {
            if ($imageChanged) {
                $image = $request->file('photo');
                $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

                // Resize to 200x200 and upload to S3 under key upload/user_images/{filename}
                $manager = new ImageManager(new Driver());
                $img = $manager->read($image)->resize(200, 200);

                $key = 'upload/user_images/' . $name_gen;
                // Try to encode and upload; fall back to putFileAs without resizing if needed
                try {
                    $encoded = $img->encode();
                    // 1) Try without visibility (works for buckets with ACLs disabled)
                    $ok = Storage::disk('s3')->put($key, (string) $encoded, [
                        'ContentType' => $image->getMimeType(),
                    ]);
                    if (!$ok) {
                        // 2) Retry with public visibility (for buckets that still use ACLs)
                        $ok = Storage::disk('s3')->put($key, (string) $encoded, [
                            'visibility' => 'public',
                            'ContentType' => $image->getMimeType(),
                        ]);
                    }
                    if (!$ok) {
                        throw new \RuntimeException('S3 put() returned false');
                    }
                } catch (\Throwable $e) {
                    // Fallback: upload original if encoding fails
                    $ok2 = Storage::disk('s3')->putFileAs('upload/user_images', $image, $name_gen, [
                        'ContentType' => $image->getMimeType(),
                    ]);
                    if (!$ok2) {
                        // Retry with visibility if first attempt failed
                        $ok2 = Storage::disk('s3')->putFileAs('upload/user_images', $image, $name_gen, [
                            'visibility' => 'public',
                            'ContentType' => $image->getMimeType(),
                        ]);
                    }
                    if (!$ok2) {
                        throw new \RuntimeException('S3 putFileAs() returned false');
                    }
                }

                $newPhotoName = $name_gen;
                $user->photo = $newPhotoName;
            }

            $user->updated_at = now();
            $user->save();

            // After successful save, remove old image if we actually replaced it
            if ($imageChanged && $oldPhotoPath && $newPhotoName && $oldPhotoPath !== $newPhotoName) {
                $this->deleteOldImage($oldPhotoPath);
            }
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile: ' . $e->getMessage(),
                    'alert-type' => 'error',
                ], 500);
            }
            return redirect()->back()->with([
                'message' => 'Failed to update profile: ' . $e->getMessage(),
                'alert-type' => 'error',
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'photo' => $user->photo,
                    'updated_at' => optional($user->updated_at)->toDateTimeString(),
                ],
            ]);
        }

        return redirect()->back()->with([
            'message' => 'Profile updated successfully',
            'alert-type' => 'success',
        ]);
    }

    protected function deleteOldImage($oldPhotoPath)
    {
        // Attempt to delete from S3; ignore failures, then attempt local cleanup
        try {
            $key = 'upload/user_images/' . ltrim((string) $oldPhotoPath, '/');
            if (Storage::disk('s3')->exists($key)) {
                Storage::disk('s3')->delete($key);
                return;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        $fullPath = public_path('upload/user_images/' . $oldPhotoPath);
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    public function PasswordUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => [
                'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                'different:old_password',
            ],
            'confirm_password' => 'required|same:new_password',
        ]);

        $hashedPassword = Auth::user()->password;
        if (!Hash::check($request->old_password, $hashedPassword)) {
            $notification = array(
                'message' => "Old password does not match!",
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }

        $user = User::find(Auth::id());
        $user->password = Hash::make($request->new_password);
        // Track who modified the password
        $user->lastmodified_by = optional(Auth::user())->name;
        $user->save();

        // End current session in history before logout
        try {
            if (Auth::check()) {
                $this->history->endSession(Auth::user(), $request, 'LOGGED OUT');
            }
        } catch (\Throwable $e) {
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $notification = array(
            'message' => 'Password updated successfully. Please log in again.',
            'alert-type' => 'success'
        );

        return redirect()->route('login')->with($notification);
    }

    /**
     * Return a human-friendly error message if the user or their business is inactive/locked; otherwise null.
     */
    protected function getUserOrBusinessStatusError(User $user): ?string
    {
        // Normalize string/boolean storage
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
                $businessLocked = (int) ($business->is_locked ?? 0) === 1;
                $businessActive = (int) ($business->is_active ?? 0) === 1;

                if ($businessLocked) {
                    return 'Your business is locked. Please contact the administrator.';
                }
                if (!$businessActive) {
                    return 'Your business is inactive. Please contact the administrator.';
                }
            } else {
                return 'Your business record is missing. Please contact the administrator.';
            }
        }

        return null;
    }

    /**
     * Get role-based redirect URL for authenticated user.
     * Used by both unlock() and ValidateCredentials() for consistent behavior.
     */
    private function getRoleBasedRedirect(User $user): string
    {
        return route('dashboard');
    }

    // =========================================================================
    // PIN MANAGEMENT
    // =========================================================================

    /**
     * Save or update the user's 4-digit PIN
     */
    public function SavePin(Request $request)
    {
        $request->validate([
            'pin' => ['required', 'digits:4'],
            'pin_confirmation' => ['required', 'same:pin'],
        ], [
            'pin.required' => 'Please enter a 4-digit PIN.',
            'pin.digits' => 'PIN must be exactly 4 digits.',
            'pin_confirmation.required' => 'Please confirm your PIN.',
            'pin_confirmation.same' => 'PIN confirmation does not match.',
        ]);

        $user = User::findOrFail(Auth::id());
        $user->pin = Hash::make($request->pin);
        $user->pin_enabled = true;
        $user->lastmodified_by = optional(Auth::user())->name;
        $user->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'PIN has been set and activated successfully.',
                'pin_enabled' => true,
            ]);
        }

        return redirect()->back()->with([
            'message' => 'PIN has been set and activated successfully.',
            'alert-type' => 'success',
        ]);
    }

    /**
     * Toggle PIN activation status
     */
    public function TogglePinStatus(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        // Cannot deactivate if PIN is not set
        if (!$user->pin && $request->boolean('enable')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please set a PIN first before activating.',
                ], 422);
            }
            return redirect()->back()->withErrors(['pin' => 'Please set a PIN first before activating.']);
        }

        $user->pin_enabled = $request->boolean('enable');
        $user->lastmodified_by = optional(Auth::user())->name;
        $user->save();

        $status = $user->pin_enabled ? 'activated' : 'deactivated';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "PIN has been {$status}.",
                'pin_enabled' => $user->pin_enabled,
            ]);
        }

        return redirect()->back()->with([
            'message' => "PIN has been {$status}.",
            'alert-type' => 'success',
        ]);
    }

    // =========================================================================
    // MULTI-STEP LOGIN FLOW
    // =========================================================================

    /**
     * Step 1: Show email-only login form
     */
    public function ShowLoginEmail()
    {
        return view('auth.login');
    }

    /**
     * Step 1: Validate email and redirect to credentials step
     */
    public function ValidateEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->back()
                ->withErrors(['email' => 'We could not find an account with that email address.'])
                ->withInput();
        }

        // Check user/business status
        $statusError = $this->getUserOrBusinessStatusError($user);
        if ($statusError) {
            return redirect()->back()
                ->withErrors(['email' => $statusError])
                ->withInput();
        }

        // Store email in session for next step
        session(['login_email' => $email, 'login_user_id' => $user->id]);

        return redirect()->route('login.credentials');
    }

    /**
     * Step 2: Show password/PIN form
     */
    public function ShowLoginCredentials()
    {
        if (!session()->has('login_email')) {
            return redirect()->route('login');
        }

        $userId = session('login_user_id');
        $user = User::find($userId);

        if (!$user) {
            session()->forget(['login_email', 'login_user_id']);
            return redirect()->route('login');
        }

        return view('auth.login-credentials', [
            'email' => session('login_email'),
            'user' => $user,
            'hasPinEnabled' => (bool) $user->pin_enabled,
        ]);
    }

    /**
     * Step 2: Validate password or PIN and complete login
     */
    public function ValidateCredentials(Request $request)
    {
        $email = session('login_email');
        $userId = session('login_user_id');

        if (!$email || !$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            session()->forget(['login_email', 'login_user_id']);
            return redirect()->route('login')->withErrors(['email' => 'User not found.']);
        }

        // Re-check status
        $statusError = $this->getUserOrBusinessStatusError($user);
        if ($statusError) {
            session()->forget(['login_email', 'login_user_id']);
            return redirect()->route('login')->withErrors(['email' => $statusError]);
        }

        $authMethod = $request->input('auth_method', 'password');
        $authenticated = false;

        if ($authMethod === 'pin') {
            if ($request->ajax()) {
                $validator = Validator::make($request->all(), ['pin' => ['required', 'digits:4']]);
                if ($validator->fails()) {
                    return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
                }
            } else {
                $request->validate(['pin' => ['required', 'digits:4']]);
            }

            if (!$user->pin || !$user->pin_enabled) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'PIN login is not available for this account.'], 422);
                }
                return redirect()->back()->withErrors(['pin' => 'PIN login is not available for this account.']);
            }

            if (Hash::check($request->pin, $user->pin)) {
                $authenticated = true;
            } else {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Invalid PIN. Please try again.'], 422);
                }
                return redirect()->back()->withErrors(['pin' => 'Invalid PIN. Please try again.']);
            }
        } else {
            if ($request->ajax()) {
                $validator = Validator::make($request->all(), ['password' => ['required', 'string']]);
                if ($validator->fails()) {
                    return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
                }
            } else {
                $request->validate(['password' => ['required', 'string']]);
            }

            if (Hash::check($request->password, $user->password)) {
                $authenticated = true;
            } else {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Invalid password. Please try again.'], 422);
                }
                return redirect()->back()->withErrors(['password' => 'Invalid password. Please try again.']);
            }
        }

        if ($authenticated) {
            session()->forget(['login_email', 'login_user_id']);

            // Check if verify-pin is enabled
            if (config('services.verify-pin', true)) {
                // Send verification code and redirect to verify page
                $verificationCode = rand(100000, 999999);
                session(['verification_code' => $verificationCode, 'user_id' => $user->id]);

                try {
                    Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));
                } catch (\Throwable $e) {
                    // Continue anyway in dev
                }

                if ($request->ajax()) {
                    return response()->json(['success' => true, 'redirect' => route('custom.verification.form')]);
                }
                return redirect()->route('custom.verification.form')
                    ->with('status', 'Verification code sent to your email. Please check your inbox.');
            }

            // Direct login without verification
            Auth::login($user);

            // Start Session History
            try {
                $this->history->startSession($user, $request);
            } catch (\Throwable $e) {
                // Log failed history start but allow login
            }

            // Determine redirect based on role using centralized helper
            $redirectRoute = $this->getRoleBasedRedirect($user);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'redirect' => $redirectRoute]);
            }
            return redirect()->intended($redirectRoute);
        }

        return redirect()->back()->withErrors(['email' => 'Authentication failed.']);
    }
}
