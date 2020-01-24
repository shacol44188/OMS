<?php

namespace App\Jobs;
use App\Jobs\EmailManager;
use App\Http\Models\v100\Log;

class MasterJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    protected function init($extra=FALSE){
        $log_data = array(
            'action' => 'KICKING OFF JOB',
            'message' => $this->job->resolveName()." : ".$extra,
            'severity' => env('LOG_SEVERITY_ZERO'),
            'userid' => 'TYOMS',
            'msg_type' => "INFORMATIONAL",
            'api_version' => "v100",
            'recd' => $this->job->getRawBody(),
            'server' => gethostname()
        );

        $log = new Log($log_data);
        $log->save();
    }

    public function handle()
    {
        //

    }

    public function failed()
    {
        $log_data = array(
            'action' => 'JOB FAILED',
            'message' => 'SOMETHING WENT WRONG',
            'severity' => env('LOG_SEVERITY_HIGH'),
            'userid' => 'TYOMS',
            'msg_type' => "ERROR",
            'api_version' => "v100",
            'recd' => serialize($this->job),
            'server' => gethostname()
        );

        $log = new Log($log_data);
        $log->save();
    }
}
