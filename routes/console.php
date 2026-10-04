<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Demo site only: with a saved demo state and SHOP_DEMO_AUTO_RESET=true, go back to it every night.
// (No terminal on the host? Use the cron URL in the README instead of schedule:run.)
Schedule::command('demo:reset')
    ->dailyAt('03:00')
    ->when(fn () => config('shop.demo_login') && (bool) env('SHOP_DEMO_AUTO_RESET', false) && \App\Services\DemoBaseline::exists());
