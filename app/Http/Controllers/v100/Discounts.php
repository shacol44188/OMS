<?php
namespace App\Http\Controllers\v100;

//use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use App\Jobs\Discounts as DiscountJob;
use App\Http\Models\v100\Discount;

class Discounts extends BaseController
{

    public function getDiscounts(){

      $discounts = Discount::all();

      $log = array(
        'action' => "GETTING DISCOUNTS",
        'message' => "FOUND: ".count($discounts),
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);

      return array(
        'status' => 0,
        'discounts' => $discounts
      );
    }

    public function syncDiscounts(){
      $this->clearDiscounts();

      $job = (new DiscountJob);

      $this->dispatchNow($job);
    }

    public function addDiscount($discount){
      Discount::truncate();
      $discount = new Discount($discount);
      $discount->save();

      $log = array(
        'action' => "AUTOMATED: ADDING DISCOUNT",
        'message' => "AUTOMATED: ADDING DISCOUNT",
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);
    }

    public function clearDiscounts(){
      if($this->user["usertype"] == 99){
        Discount::truncate();
      }
    }
/*
    public function addPcc(){
      $pcc = new Pcc(Input::all());
      $pcc->save();
    }
*/
}
