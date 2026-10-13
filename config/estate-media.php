<?php
return [
    // Outside the Git deployment root; unique to this estate's domain directory.
    'profile_path' => env('ESTATE_PROFILE_MEDIA_PATH', dirname(base_path()).'/biolync-runtime/profile-media'),
    'branding_path' => env('ESTATE_BRANDING_PATH', dirname(base_path()).'/biolync-runtime/branding'),
];
