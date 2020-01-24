<?php

namespace App\Http\Controllers\v100;

use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Http\Models\v100\CloseAccount;
use App\Jobs\EmailManager;
use App\Jobs\Logs;

class Info extends BaseController
{
    //
    public function getInfo(){
      $info = array();
      $close_call = array();
      $info[0]["maxPhotoAge"] = "1";  //MAX AGE PHOTO CAN BE, IN HOURS. NULL is disabled.
                      //EXAMPLE: $info[0]["maxPhotoAge"] = "24"
      $info[0]["closeAcctExceptions"] = "102000|106328|120436";
      $info[0]["appExpirey"] = NULL; //EXAMPLE: '10/18/2017'
      $info[0]["maxLastVisit"] = 30;

      $competition = array("AURORA","GUND","JELLYCAT","M-D","AMSCAN","MARY MEYER","FIESTA","DOUGLAS","HALLMARK","WILD REPUBLIC","PETTING ZOO","GANZ","N&J","ZOOVENIRS","WISH PETS","CUDDLEBARN","KELLIE","FIRST&MAIN","SQUISHABLE");
      sort($competition);
      $close_call[0]["competition"] = implode("|", $competition);

      //SEASONAL PROMPTS
      $seasonal = array();
      $seasonal[0]["christmas"]["start_date"] = '07/01/19';
      $seasonal[0]["christmas"]["end_date"] = '01/01/20';
      $seasonal[0]["halloween"]["start_date"] = '07/01/19';
      $seasonal[0]["halloween"]["end_date"] = '11/01/19';
      $seasonal[0]["valentines"]["start_date"] = '10/01/19';
      $seasonal[0]["valentines"]["end_date"] = '02/16/20';
      $seasonal[0]["easter"]["start_date"] = '01/01/20';
      $seasonal[0]["easter"]["end_date"] = '04/01/20';

      $log = array(
        'action' => 'GETTING INFO',
        'severity' => env('LOG_SEVERITY_ZERO')
      );
      $this->log($log);

      return $this->returnData(array(
        'status' => 0,
        'info' => $info,
        'seasonal' => $seasonal,
        'closecall' => $close_call
      ));
    }

    public function ignoreSeasonal(){
      $data = json_decode(Input::get('json'),true);

      $customer_no = $data["ignore"]["customer_no"];
      $addr_code = $data["ignore"]["addr_code"];

      error_log("Customer_no: $customer_no - Addr_code: $addr_code");

      $closeAccount = CloseAccount::where('customer_no','=',$customer_no)
                                    ->where('addr_code','=',$addr_code)
                                    ->first();

      $closeAccount->val_ind='N';
      $closeAccount->eas_ind='N';
      $closeAccount->hwn_ind='N';
      $closeAccount->chr_ind='N';

      $closeAccount->save();

      $log = array(
        'action' => 'CLOSING ACCOUNT',
        'severity' => env('LOG_SEVERITY_ZERO')
      );
      $this->log($log);

      return $this->returnData(array(
        'status' => 0
      ));
    }

    public function emailLogs(){

      $log = array(
        'action' => 'REQUESTING LOGS',
        'severity' => env('LOG_SEVERITY_ZERO')
      );
      $this->log($log);

      $email = Input::get('email');
      $rep = Input::get('rep');
      $type = Input::get('type');

      $option = $type ? $type : $rep;

      $job = (new Logs('24Hours',$email));

      $this->dispatchNow($job);

      echo "DISPATCHED JOB";
    }
}
