<?php

namespace App\Jobs;
use Carbon\Carbon;
use App\Jobs\MasterJob;
use App\Http\Models\v100\Log;

class Logs extends MasterJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $option;
    private $email;

    public function __construct($option,$email=NULL)
    {
        //
        $this->option = $option;
        $this->email = $email ? $email : 'scoleman@redacted.com';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //

        $this->init($this->option);

        switch($this->option){
          case 'cleanLogs':
            $this->cleanUp();
          break;
          case 'warnings':
            $this->getWarningLogs();
          break;
          case '24Hours':
            $this->get24HourLogs();
          break;
          case 'failed_jobs':
          break;
          default:
            $this->getRepLogs($this->option);
          break;
        }
    }

    private function cleanUp(){
      Log::where('created_at', '>', Carbon::now()->subDays(4))->delete();

        $subject = "CLEANING LOGS";
        $message = "LOGS CLEANED AT ".date('d-m-y H:i:s');

        $this->mailJob($subject, $message);
    }

    private function getRepLogs($rep){
      $subject = "LOGS: ".$rep;
      $message = "TODAYS LOGS FOR ".$rep;

      $logs = Log::where('userid','=',$rep)
                  ->where('created_at', '>', Carbon::now()->subHours(12))
                  ->get();

      $this->mailJob($subject, $message, $logs);
    }

    private function get24HourLogs(){
      $subject = "24 HOUR LOGS";
      $message = "LOGS IN PAST 24 HOURS";

      $logs = Log::where('created_at', '>', Carbon::now()->subHours(24))
                  ->get();

      $this->mailJob($subject, $message, $logs);
    }

    private function getWarningLogs(){
      $subject = "HOURLY LOGS";
      $message = "WARNINGS AND ERRORS";

      $logs = Log::where('severity','>=','1')
                  ->where('created_at', '>', Carbon::now()->subHour())
                  ->get();

      $this->mailJob($subject, $message, $logs);
    }

    private function mailJob($subject,$message,$logs=NULL){
      $job = (new EmailManager(
        array(
          'to'=>$this->email,
          'subject'=>$subject,
          'title' => $message
        ),
        $logs,
        3
      ));

      dispatch($job);
    }
}
