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

$bootstrapAvailable = !File::exists(storage_path('app/ISINSTALLED'))
    && Schema::hasTable('users')
    && DB::table('users')->count() === 0;

if ($bootstrapAvailable) {
    // Use a public URL that cannot collide with Laravel's physical bootstrap/
    // directory on shared hosting. The controller remains the bootstrap authority.
    Route::get('/setup-owner', [InstallerController::class, 'showInstaller'])
        ->middleware('guest')
        ->name('setupOwner');

    Route::post('/create-admin', [InstallerController::class, 'createAdmin'])
        ->middleware('guest')
        ->name('createAdmin');

    // The first-owner path reuses only the safe final configuration step from
    // the native installer. Database and locale mutation belong to the legacy
    // installer and must not be exposed against an already prepared production
    // .env. These named guards allow the shared Blade view to compile without
    // making those mutation endpoints usable.
    Route::post('/setup-owner/options', [InstallerController::class, 'options'])
        ->name('options');

    Route::post('/setup-owner/legacy-db', fn() => abort(404))->name('db');
    Route::post('/setup-owner/legacy-mysql', fn() => abort(404))->name('mysql');
    Route::get('/setup-owner/legacy-mysql-test', fn() => abort(404))->name('mysqlTest');
    Route::post('/setup-owner/legacy-config', fn() => abort(404))->name('editConfigInstaller');

    // Never let ordinary registration win the first-account race.
    Route::get($register, fn() => redirect()->route('setupOwner'))
        ->middleware('guest')
        ->name('register');

    Route::post($register, function () {
        abort(404);
    })->middleware('guest');
} else {
    Route::post('/validate-handle', [RegisteredUserController::class, 'validateHandle']);
    $registrationEnabled = filter_var(env('ALLOW_REGISTRATION', false), FILTER_VALIDATE_BOOLEAN);

    if($registrationEnabled || $register !== '/register') {
        Route::get($register, [RegisteredUserController::class, 'create'])
            ->middleware('guest')
            ->middleware('max.users')
            ->name('register');

        Route::post($register, [RegisteredUserController::class, 'store'])
            ->middleware('guest')
            ->middleware('max.users');
    } else {
        Route::get($register, function () {
            abort(404);
        })->name('register');

        Route::post($register, function () {
            abort(404);
        });
    }
}

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
