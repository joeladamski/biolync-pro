<?php

return [
    'manual_verification' => filter_var(env('MANUAL_USER_VERIFICATION', false), FILTER_VALIDATE_BOOLEAN),
    'email_verification' => env('REGISTER_AUTH', 'auth') === 'verified',
];
