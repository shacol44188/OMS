<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class DataSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:datasync';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'NIGHTLY DATA SYNC.';
    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $schedule->job(new SyncManager("discounts"), 'datasync')->dailyAt('16:55');
        $schedule->job(new SyncManager("customers"), 'datasync')->dailyAt('16:55');
    }
}
