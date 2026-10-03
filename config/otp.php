<?php

return [

    /*
    |--------------------------------------------------------------------------
    | One-Time Password Settings
    |--------------------------------------------------------------------------
    |
    | Controls the OTP-based login flow in App\Services\OtpService and
    | App\Http\Controllers\Auth\OtpAuthController.
    |
    */

    // Number of digits in each generated OTP.
    'length' => (int) env('OTP_LENGTH', 6),

    // How long (in minutes) a generated OTP remains valid.
    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 5),

    // Minimum seconds a user must wait before requesting another OTP
    // for the same email address (basic resend throttling).
    'resend_throttle_seconds' => (int) env('OTP_RESEND_THROTTLE_SECONDS', 45),

    /*
    |--------------------------------------------------------------------------
    | Demo OTP Display
    |--------------------------------------------------------------------------
    |
    | When true, the freshly generated OTP is echoed back in the "Send
    | OTP" response so the Vue login screen can display it directly.
    | There is no real SMS/email gateway budget for this assignment, so
    | this is what makes the login flow demoable end-to-end (including
    | for the required presentation video) without digging through
    | storage/logs/laravel.log for every code. Set this to false (and
    | configure real SMTP credentials in MAIL_* above) for anything
    | resembling a production deployment.
    |
    */
    'debug_display' => (bool) env('OTP_DEBUG_DISPLAY', true),

];
