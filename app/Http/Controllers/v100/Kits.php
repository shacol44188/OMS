<?php

namespace App\Http\Controllers\v100;

//use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Http\Models\v100\Kit;

class Kits extends BaseController
{

    public function getKits(){

        $kits = Kit::all();

        $log = array(
          'action' => "GETTING KITS",
          'message' => "FOUND: ".count($kits),
          'severity' => env('LOG_SEVERITY_ZERO')
        );

        $this->log($log);

        return array(
          'status' => 0,
          'kits' => $kits
        );
    }

    //ADMIN CONTROLS
    public function syncKits(){
      Kit::truncate();

      $job = (new SyncManager("kits"));

      $this->dispatchNow($job);
    }

    public function clearKits(){
      if($this->user["usertype"] == 99){
        Kit::truncate();
      }
    }

    //AUTOMATED SETUP

    public function addKit($kit_deets){
      $kit = new Kit($kit_deets);
      $kit->save();

      $log = array(
        'action' => "AUTOMATED: ADDING KIT",
        'message' => "AUTOMATED: ADDING KIT",
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);
    }
}
