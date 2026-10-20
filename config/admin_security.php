<?php

return [
    'require_mfa' => env('ADMIN_REQUIRE_MFA', env('APP_ENV') === 'production'),
];
