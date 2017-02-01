<?php

namespace NeoClocking\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use NeoClocking\Console\Commands\BuildSearchIndex;
use NeoClocking\Console\Commands\ImportCommand;
use NeoClocking\Console\Commands\UpdateTaskLoggedTime;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ImportCommand::class,
        BuildSearchIndex::class,
        UpdateTaskLoggedTime::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
    }
}
