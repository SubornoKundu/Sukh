<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        if ($path = config('shop.demo_baseline_path')) {
            \Illuminate\Support\Facades\File::deleteDirectory(dirname($path));
        }

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The Settings page is hidden on a live site; tests open it unless they test the hiding.
        config(['shop.settings_access' => true]);

        // The saved demo state and the uploads folder it copies are pointed at a throw-away
        // folder, so tests never read, overwrite or wipe a real saved state or real pictures.
        $scratch = sys_get_temp_dir().'/shopease-test-'.bin2hex(random_bytes(4));
        config(['shop.demo_baseline_path' => $scratch.'/baseline', 'shop.demo_uploads_path' => $scratch.'/uploads']);
    }
}
