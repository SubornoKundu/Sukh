<?php

namespace App\Console\Commands;

use App\Services\DemoBaseline;
use Illuminate\Console\Command;

class DemoReset extends Command
{
    protected $signature = 'demo:reset {--save : save the site as it is now instead of restoring}';

    protected $description = 'Put the demo site back to its saved state (or, with --save, save the state as it is now)';

    public function handle(): int
    {
        if (! config('shop.demo_login')) {
            $this->error('This is not a demo site (SHOP_DEMO_LOGIN is false). Nothing was done.');

            return self::FAILURE;
        }

        try {
            if ($this->option('save')) {
                $meta = DemoBaseline::save();
                $this->info('Saved ('.array_sum($meta['tables']).' rows, '.$meta['uploads'].' pictures).');

                return self::SUCCESS;
            }

            DemoBaseline::restore();
            $this->info('Demo site is back to its saved state.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
