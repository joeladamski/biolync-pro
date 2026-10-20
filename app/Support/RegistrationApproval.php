<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

class RegistrationApproval
{
    /** Persist public signups with their approval status before any login. */
    public static function create(User $user): void
    {
        $user->role = 'user';
        $user->block = config('registration.manual_verification') ? 'yes' : 'no';
        $user->save();

        // Admin approval is independent of email ownership verification.
        if ($user->block === 'yes') {
            try {
                Mail::send('auth.user-confirmation', [
                    'user' => $user->name,
                    'email' => $user->email,
                ], function ($message) {
                    $message->to(env('ADMIN_EMAIL'))->subject('New user registration');
                });
            } catch (\Exception $e) {
                // A notification failure must never approve or discard the account.
                report($e);
            }
        }
    }
}
