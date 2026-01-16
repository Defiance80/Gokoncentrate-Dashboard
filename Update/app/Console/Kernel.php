<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [

        'Modules\Subscriptions\Console\Commands\CheckSubscription',
        \App\Console\Commands\OptimizeImages::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('queue:work --tries=3 --stop-when-empty')->withoutOverlapping();

        
        
        // Notification commands - run daily
        $schedule->command('subscriptions:notify')->dailyAt('09:00')->description('Send subscription expiry reminders');
        $schedule->command('reminder:notify')->dailyAt('10:00')->description('Send upcoming content reminders');
        $schedule->command('continuewatch:notify')->dailyAt('11:00')->description('Send continue watching reminders');

        $schedule->call(function () {
            \App\Models\Device::whereNotNull('session_id')
                ->where('last_activity', '<', now()->subMinutes(config('session.lifetime')))
                ->delete();
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
