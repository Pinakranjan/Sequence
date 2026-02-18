<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\LoginHistoryService;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Utility\BusinessController;
use App\Http\Controllers\Utility\UserController;
use App\Http\Controllers\Utility\BusinessTermsController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\TransporterController;


Route::get('/', function () {
    // If user is authenticated, redirect to dashboard.
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
})->name('home');

// Lightweight health endpoint used by CI/CD health checks
Route::get('/healthz', function () {
    return response('OK', 200);
})->name('healthz');

// Dynamic Favicon Route
// Dynamic Favicon Route
Route::get('/favicon.svg', [\App\Http\Controllers\FaviconController::class, 'show'])->name('favicon.dynamic');

require __DIR__ . '/auth.php';

// Dashboard route
Route::get('/dashboard', function () {
    $user = Auth::user();
    $role = strtolower(trim((string) optional($user)->role));

    // Enforce admin access
    $allowed = ['super admin', 'admin', 'user'];
    if (!in_array($role, $allowed, true)) {
        abort(403, 'Forbidden');
    }

    return view('admin.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/admin/logout', [AdminController::class, 'AdminLogout'])->name('admin.logout');
    Route::post('/admin/clear-cache', [AdminController::class, 'clearCache'])->name('admin.cache.clear');
    Route::get('/profile', [AdminController::class, 'AdminProfile'])->name('admin.profile');

    Route::get('/profiles', function () {
        return redirect()->route('admin.profile');
    })->name('admin.profiles.index');
});

Route::post('/admin/login', [AdminController::class, 'AdminLogin'])->name('admin.login');

// Multi-step login flow
Route::post('/login/email', [AdminController::class, 'ValidateEmail'])->name('login.email');
Route::get('/login/credentials', [AdminController::class, 'ShowLoginCredentials'])->name('login.credentials');
Route::post('/login/credentials', [AdminController::class, 'ValidateCredentials'])->name('login.credentials.validate');

// Lock screen routes
Route::get('/lock', [AdminController::class, 'showLockScreen'])->name('lock.show');
Route::post('/unlock', [AdminController::class, 'unlock'])->name('lock.unlock');
Route::get('/lock/logout', [AdminController::class, 'lockLogout'])->name('lock.logout');

Route::post('/register/start', [AdminController::class, 'RegisterStart'])->name('custom.register.start');
Route::post('/validate-business-code', [AdminController::class, 'validateBusinessCode'])->name('validate.business.code');
Route::get('/verify', [AdminController::class, 'ShowVerification'])->name('custom.verification.form');
Route::post('/verify', [AdminController::class, 'VerificationVerify'])->name('custom.verification.verify');

// Forgot password (3-step: email -> new password -> code)
Route::get('/password/forgot', [AdminController::class, 'showForgotPasswordForm'])->name('password.code.request');
Route::post('/password/forgot', [AdminController::class, 'forgotEmail'])->name('password.code.email');
Route::get('/password/forgot/password', [AdminController::class, 'showForgotPasswordNewForm'])->name('password.code.password.form');
Route::post('/password/forgot/password', [AdminController::class, 'sendResetCode'])->name('password.code.password');
Route::get('/password/reset', [AdminController::class, 'showResetPasswordForm'])->name('password.code.form');
Route::post('/password/reset', [AdminController::class, 'resetPassword'])->name('password.code.update');

// AJAX endpoint to check authentication/session liveness used by heartbeat
Route::get('/__auth-check', function (Request $request, LoginHistoryService $history) {
    if (!Auth::check()) {
        return response()->json([
            'authenticated' => false,
            'redirect' => route('login'),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache');
    }

    $user = Auth::user();
    // Ensure this specific session is still active according to login history
    $alive = $history->touchOrCheck($user, $request);
    if (!$alive) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json([
            'authenticated' => false,
            'redirect' => route('login'),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache');
    }

    return response()->json([
        'authenticated' => true,
        'redirect' => route('home'),
    ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache');
})->name('auth.check');

Route::middleware('auth')->group(function () {
    // Only allow access to these routes based on config('services.role_pages') mapping
    Route::middleware('role.pages')->group(function () {
        Route::controller(BusinessController::class)->group(function () {
            Route::get('/businesses', 'AllBusinesses')->name('business.register');
            Route::get('/businesses/list', 'ListBusinesses')->name('list.businesses');
            Route::get('/businesses/assigned', 'ListAccessibleBusinessesForPicker')->name('list.assigned.businesses');
            Route::get('/business/users', 'ListBusinessUsers')->name('business.users.list');
            Route::post('/business/create', 'AddBusiness')->name('add.business');
            Route::post('/business/update', 'UpdateBusiness')->name('update.business');
            Route::post('/business/delete', 'DeleteBusiness')->name('delete.business');
            Route::post('/business/set-active', 'SetActiveBusiness')->name('setactive.business');
            Route::post('/business/set-locked', 'SetLockedBusiness')->name('setlocked.business');
            Route::post('/business/purge-data', 'PurgeBusinessData')->name('purge.business.data');
            Route::get('/super-users/business-access', 'ListSuperUserBusinessAssignments')->name('superuser.business.assignments');
            Route::post('/super-users/business-access/sync', 'SyncSuperUserBusinessAssignments')->name('superuser.business.assignments.sync');
        });

        Route::controller(UserController::class)->group(function () {
            Route::get('/users', 'AllUsers')->name('user.register');
            Route::get('/users/list', 'ListUsers')->name('list.users');
            Route::post('/user/update', 'UpdateUser')->name('update.user');
            Route::post('/user/set-active', 'SetActiveUser')->name('setactive.user');
            Route::post('/user/set-locked', 'SetLockedUser')->name('setlocked.user');
            Route::post('/user/revoke-session', 'RevokeSession')->name('revoke.user.session');
            Route::post('/user/delete', 'DeleteUser')->name('delete.user');
            Route::get('/user/forms/list', 'ListUserForms')->name('list.user.forms');
            Route::post('/user/forms/save', 'SaveUserPermissions')->name('save.user.permissions');
        });

        // ── Master Module Routes ──────────────────────────────────────────
        Route::controller(ProductController::class)->group(function () {
            Route::get('/products', 'index')->name('product.master');
            Route::get('/products/list', 'ListProducts')->name('list.products');
            Route::post('/product/create', 'AddProduct')->name('add.product');
            Route::post('/product/update', 'UpdateProduct')->name('update.product');
            Route::post('/product/delete', 'DeleteProduct')->name('delete.product');
            Route::post('/product/set-active', 'SetActiveProduct')->name('setactive.product');
        });

        Route::controller(CustomerController::class)->group(function () {
            Route::get('/customers', 'index')->name('customer.master');
            Route::get('/customers/list', 'ListCustomers')->name('list.customers');
            Route::post('/customer/create', 'AddCustomer')->name('add.customer');
            Route::post('/customer/update', 'UpdateCustomer')->name('update.customer');
            Route::post('/customer/delete', 'DeleteCustomer')->name('delete.customer');
            Route::post('/customer/set-active', 'SetActiveCustomer')->name('setactive.customer');
        });

        Route::controller(SupplierController::class)->group(function () {
            Route::get('/suppliers', 'index')->name('supplier.master');
            Route::get('/suppliers/list', 'ListSuppliers')->name('list.suppliers');
            Route::post('/supplier/create', 'AddSupplier')->name('add.supplier');
            Route::post('/supplier/update', 'UpdateSupplier')->name('update.supplier');
            Route::post('/supplier/delete', 'DeleteSupplier')->name('delete.supplier');
            Route::post('/supplier/set-active', 'SetActiveSupplier')->name('setactive.supplier');
        });

        Route::controller(TransporterController::class)->group(function () {
            Route::get('/transporters', 'index')->name('transporter.master');
            Route::get('/transporters/list', 'ListTransporters')->name('list.transporters');
            Route::post('/transporter/create', 'AddTransporter')->name('add.transporter');
            Route::post('/transporter/update', 'UpdateTransporter')->name('update.transporter');
            Route::post('/transporter/delete', 'DeleteTransporter')->name('delete.transporter');
            Route::post('/transporter/set-active', 'SetActiveTransporter')->name('setactive.transporter');
        });

    });

    // Terms & Conditions per Business
    Route::controller(BusinessTermsController::class)->group(function () {
        Route::get('/business/terms', 'get')->name('business.terms.get');
        Route::post('/business/terms', 'update')->name('business.terms.update');
    });

    Route::controller(AdminController::class)->group(function () {
        Route::get('/profile', 'AdminProfile')->name('admin.profile');
        Route::post('/profile/store', 'ProfileStore')->name('profile.store');
        Route::post('/admin/password/update', 'PasswordUpdate')->name('admin.password.update');
        // PIN management
        Route::post('/profile/pin', 'SavePin')->name('profile.pin.save');
        Route::post('/profile/pin/toggle', 'TogglePinStatus')->name('profile.pin.toggle');
    });

    // Image Bank
});
