<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class ServerHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'HEALTH MONITOR';
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
      $servers = array(
        'TYOMS1' => "tyoms1.redacted.com",
        'TYOMS2' => "tyoms2.redacted.com"
      );

      $ports = array(
        'API' => '443',
        'MONGO' => '27017'
      );

      $errors = array();

      $waitTimeoutInSeconds = 1;
      foreach($servers as $server=>$address){
        foreach($ports as $service=>$port){
          if(!$fp = fsockopen($address,$port,$errCode,$errStr,$waitTimeoutInSeconds)){
            $errors[] = "SERVER: $server\nSERVICE: $service";
          }
        }
      }

      if(count($errors) > 0){
        $message = implode("\n\n", $errors);
        mail('scoleman@redacted.com','URGENT - CONNECTIVITY ISSUE WITH TYOMS', $message);
      }
    }
}
