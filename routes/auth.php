<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if(config('advanced-config.register_url') != '') {
    $register = config('advanced-config.register_url');
} else {
    $register = "/register";
}

if(config('advanced-config.login_url') != '') {
    $login = config('advanced-config.login_url');
} else {
    $login = "/login";
}

if(config('advanced-config.forgot_password_url') != '') {
    $forgot_password = config('advanced-config.forgot_password_url');
} else {
    $forgot_password = "/forgot-password";
}

$bootstrapAvailable = static function (): bool {
    return !File::exists(storage_path('app/ISINSTALLED'))
        && Schema::hasTable('users')
        && DB::table('users')->count() === 0;
};

Route::get('/setup-owner', [InstallerController::class, 'showInstaller'])
    ->middleware('guest')
    ->name('setupOwner');

Route::post('/create-admin', [InstallerController::class, 'createAdmin'])
    ->middleware('guest')
    ->name('createAdmin');

Route::post('/validate-handle', [RegisteredUserController::class, 'validateHandle']);

$registrationEnabled = filter_var(env('ALLOW_REGISTRATION', false), FILTER_VALIDATE_BOOLEAN);
$registrationAvailable = $registrationEnabled || $register !== '/register';

// Resolve bootstrap state only when a registration request arrives. Keeping the
// database check out of route registration allows deployment/composer/artisan
// bootstrap to load routes before production database credentials are present.
Route::get($register, function () use ($bootstrapAvailable, $registrationAvailable) {
    if ($bootstrapAvailable()) {
        return redirect()->route('setupOwner');
    }

    if (!$registrationAvailable) {
        abort(404);
    }

    return app(RegisteredUserController::class)->create();
})
    ->middleware(['guest', 'max.users'])
    ->name('register');

Route::post($register, function (\Illuminate\Http\Request $request) use ($bootstrapAvailable, $registrationAvailable) {
    // Never let ordinary registration win the first-account race.
    if ($bootstrapAvailable() || !$registrationAvailable) {
        abort(404);
    }

    return app(RegisteredUserController::class)->store($request);
})
    ->middleware(['guest', 'max.users']);


// Stable finalization routes: always registered so createAdmin() can safely
// redirect by name during the same request in which the first owner is created.
// Authorization/state checks happen inside each route/controller action.
Route::get('/setup-owner/finalize', [InstallerController::class, 'showOwnerFinalize'])
    ->middleware('auth')
    ->name('setupOwnerFinalize');

Route::post('/setup-owner/options', [InstallerController::class, 'options'])
    ->middleware('auth')
    ->name('setupOwnerOptions');

Route::get($login, [AuthenticatedSessionController::class, 'create'])
                ->middleware('guest')
                ->name('login');

Route::post($login, [AuthenticatedSessionController::class, 'store'])
                ->middleware('guest');

Route::get( $forgot_password, [PasswordResetLinkController::class, 'create'])
                ->middleware('guest')
                ->name('password.request');

Route::post( $forgot_password, [PasswordResetLinkController::class, 'store'])
                ->middleware('guest')
                ->name('password.email');

Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
                ->middleware('guest')
                ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
                ->middleware('guest')
                ->name('password.update');

Route::get('/verify-email', [EmailVerificationPromptController::class, '__invoke'])
                ->middleware('auth')
                ->name('verification.notice');

Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
                ->middleware(['auth', 'signed', 'throttle:6,1'])
                ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware(['auth', 'throttle:6,1'])
                ->name('verification.send');

Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->middleware('auth')
                ->name('password.confirm');

Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store'])
                ->middleware('auth');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
                ->middleware('auth')
                ->name('logout');

Route::get('/blocked', function () {
                    $user = Auth::user();
                    if ($user && $user->block == 'yes') {
                        return view('auth.blocked');
                    } else {
                        return redirect(url('dashboard'));
                    }
                })->name('blocked');
