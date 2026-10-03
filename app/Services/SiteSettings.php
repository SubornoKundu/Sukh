<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-editable site settings (name, logo, colours, contact details...).
 *
 * Saved values live in the `settings` table and override the defaults from
 * config/shop.php / .env. They are copied into the Laravel config at boot
 * (see AppServiceProvider), so the rest of the app keeps reading config().
 */
class SiteSettings
{
    public const CACHE_KEY = 'site.settings';

    public const DEFAULT_COLOR = '#16a34a';

    /** Setting keys that hold text (shown/validated in the admin form). */
    public const KEYS = [
        'site_name', 'tagline', 'meta_description', 'logo', 'favicon', 'primary_color',
        'announcement_text', 'announcement_link', 'footer_text',
        'phone', 'email', 'address', 'hours', 'whatsapp',
        'facebook', 'instagram', 'youtube', 'x',
        'shipping_fee', 'free_shipping_over', 'low_stock_threshold', 'bkash_number', 'nagad_number',
    ];

    /**
     * Defaults when nothing has been saved yet.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'site_name' => (string) env('APP_NAME', 'ShopEase'),
            'tagline' => 'Your everyday online shop in Bangladesh. Genuine products, fast delivery and easy returns.',
            'meta_description' => 'Shop genuine products online with fast delivery and easy returns.',
            'logo' => null,
            'favicon' => null,
            'primary_color' => self::DEFAULT_COLOR,
            'announcement_text' => null,
            'announcement_link' => null,
            'footer_text' => null,
            'phone' => config('shop.contact.phone'),
            'email' => config('shop.contact.email'),
            'address' => config('shop.contact.address'),
            'hours' => config('shop.contact.hours'),
            'whatsapp' => config('shop.whatsapp'),
            'facebook' => config('shop.social.facebook'),
            'instagram' => config('shop.social.instagram'),
            'youtube' => config('shop.social.youtube'),
            'x' => config('shop.social.x'),
            'shipping_fee' => config('shop.shipping_fee'),
            'free_shipping_over' => config('shop.free_shipping_over'),
            'low_stock_threshold' => config('shop.low_stock_threshold'),
            'bkash_number' => config('shop.payments.bkash.number'),
            'nagad_number' => config('shop.payments.nagad.number'),
        ];
    }

    /**
     * Stored values only (no defaults), cached.
     *
     * @return array<string, string|null>
     */
    public static function stored(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $rows = Setting::query()->pluck('value', 'key')->all();
        Cache::forever(self::CACHE_KEY, $rows);

        return $rows;
    }

    /**
     * Defaults with saved values on top. An empty saved text means "use the
     * default" for required fields and "off" for optional ones (handled by the
     * caller via null-able keys).
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $values = self::defaults();

        foreach (self::stored() as $key => $value) {
            if (array_key_exists($key, $values)) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(array $data): void
    {
        foreach ($data as $key => $value) {
            if (! in_array($key, self::KEYS, true)) {
                continue;
            }

            Setting::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Copy the saved settings into the config so every config() call sees them.
     */
    public static function applyToConfig(): void
    {
        try {
            $s = self::all();
        } catch (\Throwable) {
            return; // table not migrated yet (fresh install / migrate:fresh)
        }

        $name = trim((string) ($s['site_name'] ?? '')) ?: 'ShopEase';
        $color = self::normalizeColor($s['primary_color'] ?? null);

        config([
            'app.name' => $name,
            'mail.from.name' => $name,
            'site' => [
                'name' => $name,
                'tagline' => (string) ($s['tagline'] ?? ''),
                'meta_description' => (string) ($s['meta_description'] ?? ''),
                'logo' => $s['logo'] ?: null,
                'favicon' => $s['favicon'] ?: null,
                'primary_color' => $color,
                'palette_css' => $color === self::DEFAULT_COLOR ? '' : self::paletteCss($color),
                'announcement_text' => $s['announcement_text'] ?: null,
                'announcement_link' => $s['announcement_link'] ?: null,
                'footer_text' => $s['footer_text'] ?: null,
            ],
            'shop.contact' => [
                'phone' => $s['phone'] ?? null,
                'email' => $s['email'] ?? null,
                'address' => $s['address'] ?? null,
                'hours' => $s['hours'] ?? null,
            ],
            'shop.whatsapp' => (string) ($s['whatsapp'] ?? ''),
            'shop.social' => [
                'facebook' => $s['facebook'] ?? null,
                'instagram' => $s['instagram'] ?? null,
                'youtube' => $s['youtube'] ?? null,
                'x' => $s['x'] ?? null,
            ],
            'shop.shipping_fee' => (float) ($s['shipping_fee'] ?? 0),
            'shop.free_shipping_over' => (float) ($s['free_shipping_over'] ?? 0),
            'shop.low_stock_threshold' => (int) ($s['low_stock_threshold'] ?? 5),
            'shop.payments.bkash.number' => (string) ($s['bkash_number'] ?? ''),
            'shop.payments.nagad.number' => (string) ($s['nagad_number'] ?? ''),
        ]);
    }

    public static function normalizeColor(?string $color): string
    {
        $color = strtolower(trim((string) $color));

        return preg_match('/^#[0-9a-f]{6}$/', $color) ? $color : self::DEFAULT_COLOR;
    }

    /**
     * Re-define Tailwind's "green" scale from one brand colour. The whole UI
     * uses green-*, so this recolours buttons, links, badges and focus rings.
     */
    public static function paletteCss(string $color): string
    {
        $mix = fn (int $pct, string $with) => "color-mix(in oklab, {$color} {$pct}%, {$with})";

        $scale = [
            '50' => $mix(7, 'white'),
            '100' => $mix(14, 'white'),
            '200' => $mix(28, 'white'),
            '300' => $mix(48, 'white'),
            '400' => $mix(72, 'white'),
            '500' => $color,
            '600' => $color,
            '700' => $mix(80, 'black'),
            '800' => $mix(64, 'black'),
            '900' => $mix(50, 'black'),
            '950' => $mix(34, 'black'),
        ];

        $css = ':root{';
        foreach ($scale as $step => $value) {
            $css .= "--color-green-{$step}:{$value};";
        }

        return $css.'}';
    }
}
