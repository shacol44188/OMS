<?php

namespace App\Http\Controllers\v100;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Input;
use App\Http\Models\v100\Log;

class MasterController extends BaseController
{
    //
    protected $user;

    //GET AUTHENTICATED USER
    public function __construct(){
      $this->user = app('Illuminate\Contracts\Auth\Guard')->user();

      $log = array(
        'action' => "AUTHORIZED ACCESS",
        'message' => "USER ACCESSING API",
        'severity' => getenv('LOG_SEVERITY_ZERO') //SEVERITY (0-3) INDICATES URGENCY. ZERO BEING NONE OR INFORMATIONAL, 3 BEING FATAL
      );

    //  $this->log($log);
    }

    public function returnData($data){
      $data["user_info"] = $this->user;
      return $data;
    }


    //$data in format ('action','message','severity')
    //FUNCTION log EXPECTS MESSAGE,MSG_TYPE,SEVERITY TO BE PASSED IN AS ARRAY
    public function log($data){

      $type = "";

      switch($data["severity"]){
        case 0: $type = "INFORMATION";
        break;
        case 1: $type = "WARNING";
        break;
        case 2: $type = "ERROR";
        break;
        default: $type = "FATAL";
        break;
      }


      $data["userid"] = $this->user["userid"];
      $data["msg_type"] = $type;
      $data["api_version"] = "v100";
      $data["recd"] = json_encode(Input::all());
      $data["server"] = gethostname();

      $log = new Log($data);
      $log->save();
    }

}
