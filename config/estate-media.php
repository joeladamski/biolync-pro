<?php
return [
    // Outside the Git deployment root; unique to this estate's domain directory.
    'branding_path' => env('ESTATE_BRANDING_PATH', dirname(base_path()).'/biolync-runtime/branding'),
];
