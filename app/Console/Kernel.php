<?php

namespace App\Console;

use App\Jobs\SyncManager;
use App\Jobs\Customers;
use App\Jobs\Logs;
use App\Jobs\Discounts;
use App\Jobs\ServerHealthMonitor;
use App\Console\Commands\EnsureQueueListenerIsRunning;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        EnsureQueueListenerIsRunning::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $servers = array(
            'TYOMS1' => array(
                'host' => 'tyoms1.redacted.com',
                'services' => array(
                    'API' => '443',
                    'MONGO' => '27017'
                )
        ));

        $errors = array();
        $waitTimeoutInSeconds = 1;

        $available = true;

        foreach($servers as $server) {
            $host = $server["host"];
            foreach ($server["services"] as $service => $port) {
                try {
                    $fp = @fsockopen($host, $port, $errCode, $errStr, $waitTimeoutInSeconds);
                    if (!$fp) {
                        $available = false;
                    }
                } catch (\ErrorException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        $proceed = (gethostname() != $servers['TYOMS1']["host"] && !$available) ? TRUE : (gethostname() == $servers["TYOMS1"]["host"]) ? TRUE : FALSE;

        if($proceed) {

            //WORKER QUEUE PROCESS IS RUNNING
            $schedule->command('queue:checkup')
                ->everyFiveMinutes();

            //DATA SYNCS
            $schedule->job(new Discounts)
                ->timezone('America/Chicago')
                ->dailyAt('05:30');

            $schedule->job(new Customers("customers"))
                ->timezone('America/Chicago')
                ->everyFifteenMinutes()
                ->between('7:00', '14:30');

            $schedule->job(new Customers("changed"))
                ->timezone('America/Chicago')
                ->everyFiveMinutes()
                ->between('10:00', '22:00');

            $schedule->job(new Logs("warnings"))
                ->timezone('America/Chicago')
                ->hourly()
                ->between('10:00', '22:00');
        }


    }
}
