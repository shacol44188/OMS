<?php

namespace App\Http\Controllers\v100;

use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use App\Jobs\EmailManager;
use App\Http\Models\v100\User;
use App\Http\Models\v100\Rep;

class Auth extends BaseController
{
    //
    public function login($ts=FALSE){

      $logged_user = app('Illuminate\Contracts\Auth\Guard')->user();

      $salesperson = Rep::where('salesperson_no','=',$logged_user["userid"])->first();
      $salesperson["api_token"] = $logged_user["api_token"];

      return $this->returnData(array(
        'status' => 0,
    //    'user' => app('Illuminate\Contracts\Auth\Guard')->user()
        'user' => $salesperson
      ));
    }

    public function validateToken(){

      return $this->returnData(array(
        'status' => 0
      ));

    }
}
