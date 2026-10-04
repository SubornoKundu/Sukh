<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DemoBaseline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * "Save the demo as it looks now". Only available on a demo site, and only while
 * SHOP_SETTINGS_ACCESS is on, so visitors who are given the admin login cannot
 * overwrite the saved copy.
 */
class DemoBaselineController extends Controller
{
    public function store(): RedirectResponse
    {
        abort_unless(config('shop.demo_login') && config('shop.settings_access'), 404);

        try {
            $meta = DemoBaseline::save();
        } catch (\Throwable $e) {
            Log::error('Saving the demo state failed: '.$e->getMessage());

            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Saved. "Reset demo data" now brings the site back to exactly this ('.($meta['tables']['products'] ?? 0).' products, '.$meta['uploads'].' uploaded pictures).');
    }
}
