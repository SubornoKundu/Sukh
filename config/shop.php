<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storefront Settings
    |--------------------------------------------------------------------------
    */

    // Flat shipping fee added to every order at checkout.
    'shipping_fee' => (float) env('SHOP_SHIPPING_FEE', 60),

    // Orders whose total (after discount) reaches this amount ship free.
    // Set to 0 to disable free shipping.
    'free_shipping_over' => (float) env('SHOP_FREE_SHIPPING_OVER', 5000),

    // Products with this many units (or fewer) left show up as "low stock"
    // on the admin dashboard.
    'low_stock_threshold' => (int) env('SHOP_LOW_STOCK_THRESHOLD', 5),

    'currency' => env('SHOP_CURRENCY', 'BDT'),

    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', '৳'),


    /*
    |--------------------------------------------------------------------------
    | Contact details, WhatsApp and social links (header / footer)
    |--------------------------------------------------------------------------
    */
    'contact' => [
        'phone' => env('SHOP_PHONE', '+880 1700-000000'),
        'email' => env('SHOP_EMAIL', 'support@shopease.test'),
        'address' => env('SHOP_ADDRESS', 'House 12, Road 5, Dhanmondi, Dhaka 1209, Bangladesh'),
        'hours' => env('SHOP_HOURS', 'Sat - Thu, 10:00 AM - 8:00 PM'),
    ],

    // International format without "+" or spaces, used for wa.me links.
    'whatsapp' => env('SHOP_WHATSAPP', '8801700000000'),

    'social' => [
        'facebook' => env('SHOP_FACEBOOK', 'https://facebook.com'),
        'instagram' => env('SHOP_INSTAGRAM', 'https://instagram.com'),
        'youtube' => env('SHOP_YOUTUBE', 'https://youtube.com'),
        'x' => env('SHOP_X', 'https://x.com'),
    ],

    // Shown in the "Shipping Terms" tab when a product has no text of its own.
    'default_shipping_info' => env(
        'SHOP_SHIPPING_INFO',
        "Inside Dhaka: delivery within 1-2 working days.\nOutside Dhaka: delivery within 3-5 working days.\nDelivery charge is added at checkout and is free on large orders.\nCash on delivery is available - check the product before you pay.\n7-day replacement if the product is damaged or not as described."
    ),

    // Demo manual mobile-banking numbers shown at checkout (not real accounts).
    'payments' => [
        'bkash' => ['number' => env('SHOP_BKASH_NUMBER', '01700-000000'), 'type' => 'Personal'],
        'nagad' => ['number' => env('SHOP_NAGAD_NUMBER', '01800-000000'), 'type' => 'Personal'],
    ],

    // Scaling: how long (seconds) the home page blocks, the best-seller ranking and the
    // admin dashboard totals may be reused before they are recalculated. Admin edits to
    // products / brands / categories / banners refresh the storefront immediately.
    // 0 turns caching off (that is what the automated tests use).
    'storefront_cache_seconds' => (int) env('SHOP_STOREFRONT_CACHE', app()->runningUnitTests() ? 0 : 120),
    'popular_cache_seconds' => (int) env('SHOP_POPULAR_CACHE', app()->runningUnitTests() ? 0 : 600),
    'stats_cache_seconds' => (int) env('SHOP_STATS_CACHE', app()->runningUnitTests() ? 0 : 120),

    // One-click demo / "Google" sign in on the login page. Turn off in production.
    'demo_login' => (bool) env('SHOP_DEMO_LOGIN', true),

    // Demo site: "Reset demo data" returns to the state saved with "Save as demo state" (kept in files under
    // storage/app/demo-baseline). DEMO_RESET_TOKEN lets a cron job do that automatically (see README).
    'demo_reset_token' => env('DEMO_RESET_TOKEN'),
    'demo_baseline_path' => null,   // tests point these two at a temporary folder
    'demo_uploads_path' => null,

    // Admin > Settings (shop name, logo, colours...) is hidden from the admin panel and its
    // address gives "not found" unless this is true. Keep it false on a site you hand over.
    // To change the logo or name yourself: set SHOP_SETTINGS_ACCESS=true in .env, save, edit, set it back.
    'settings_access' => (bool) env('SHOP_SETTINGS_ACCESS', false),

    // Behind Cloudflare / a load balancer / a hosting proxy: set TRUSTED_PROXIES=* (or a comma list
    // of proxy IPs) in .env so visitors' real IP addresses are used (login limits are per IP).
    'trusted_proxies' => env('TRUSTED_PROXIES'),
];
