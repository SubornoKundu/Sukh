<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSLCommerz (Demo)
    |--------------------------------------------------------------------------
    |
    | ShopEase ships with a self-contained DEMO of the SSLCommerz checkout
    | flow -- see App\Services\SslCommerzDemoGateway. No real merchant
    | account or outbound API call is involved, so the store/password
    | below are placeholders. Swap in the real sslcommerz/sslcommerz-php
    | package and real credentials to go live.
    |
    */
    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID', 'shopease_demo'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD', 'demo_password'),
        'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
    ],

];
