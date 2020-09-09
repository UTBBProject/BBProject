<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */

    protected $commands = [
        '\App\Console\Commands\CronJobs'
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//        Log::debug('debug kadi');
        if (env('CRON_EARNINGS_LOG_ENABLE'))
            $schedule->command('cron:earningsLog')->dailyAt(env('CRON_EARNINGS_LOG_TIME'));
    }
}
