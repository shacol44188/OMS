<?php
namespace App\Http\Controllers\v100;

//use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Jobs\SyncManager;
use App\Jobs\Customers AS CustomerJob;
use App\Http\Controllers\v100\Kits;
use App\Http\Models\v100\Customer;
use App\Http\Models\v100\Pcc;
use App\Http\Models\v100\CloseAccount;

class Customers extends BaseController
{

    private $limit=10000;

    public function getCustomers($skip){

      if($this->user["tradeshow"] == "Y"){
        $customers = Customer::where('cust_div_code','=','FLD')
                      ->take(intval($this->limit))
                      ->skip(intval($skip*$this->limit))
                      ->get();

        $noOfRecs = Customer::where('cust_div_code','=','FLD')->count();
      }
      else{
        $customers = Customer::where('salesperson_no','=',$this->user["userid"])->get();
        $noOfRecs = Customer::where('salesperson_no','=',$this->user["userid"])->count();
      }

      $log = array(
        'action' => 'GETTING CUSTOMERS',
        'message' => "PASS:$skip, FOUND ".count($customers),
        'severity' => count($customers) > 0 ? env('LOG_SEVERITY_ZERO') : env('LOG_SEVERITY_LOW')
      );

      $this->log($log);

      return $this->returnData(array(
        'status' => 0,
        'customers' => $customers,
        'noOfRecs'=>$noOfRecs,
        'limit'=>$this->limit
      ));
    }

    public function getCustomer($customer_loc){
      $customer_deets = explode("-", $customer_loc);

      $customer = Customer::where('customer_no','=',$customer_deets[0])
                            ->where('addr_code','=',$customer_deets[1])
                            ->first();

      $valid = isset($customer->customer_no);

      $log = array(
            'action' => 'REFRESHING CUSTOMER',
            'message' => "FOUND $customer->customer_no-$customer->addr_code",
            'severity' => $valid ? env('LOG_SEVERITY_ZERO') : env('LOG_SEVERITY_LOW')
      );

      $this->log($log);

      return $this->returnData(array(
        'status'=>0,
        'customer'=>$customer
      ));

    }

    public function closeAccount(){
      $params = Input::all();

      $params["otherPlush"] = isset($params["plushLines"]) ? "Y" : "N";
      $params["contact_date"] = date('m/d/Y',strtotime($params["visit_date"]));
      $params["user_id"] = $this->user["userid"];
      $params["close_flag"] = "Y";
      $params["contact_name"] = $params["speakWith"];
      $params["linescarried"] = $params["plushLines"];
      $params["busclosed"] = "N";

      $close = new CloseAccount($params);
      $close->save();

      $log = array(
        'action' => 'CLOSE ACCOUNT',
        'message' => $params["customer_no"]."-".$params["addr_code"],
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);

      return $this->returnData(array(
        'status' => 0
      ));

    }

    //AUTOMATED SETUP
    public function addBulkCustomer(){
      $customers = Input::all();

      foreach($customers as $customer){
        $new_customer = new Customer($customer);
        $new_customer->save();
      }
    }

    //ADMIN CONTROLS
    public function syncCustomers($rep=FALSE){
        $log = array(
            'action' => 'SYNCING CUSTOMERS',
            'message' => 'BEGINNING PROCESS: SYNCING CUSTOMERS '.$rep,
            'severity' => env('LOG_SEVERITY_ZERO')
        );

        $this->log($log);

      $job = (new CustomerJob("customers",$rep));

      $this->dispatch($job);
    }
    public function syncChanged(){
        $log = array(
            'action' => 'SYNCING CHANGED CUSTOMERS',
            'message' => 'BEGINNING PROCESS: SYNCING CHANGED CUSTOMERS',
            'severity' => env('LOG_SEVERITY_ZERO')
        );
        $this->log($log);

        $job = (new CustomerJob("changed"));

        $this->dispatch($job);
    }
    public function syncCustomer($customer){
        $log = array(
            'action' => 'SYNCING CUSTOMER',
            'message' => 'BEGINNING PROCESS: SYNCING CUSTOMER '.$customer,
            'severity' => env('LOG_SEVERITY_ZERO')
        );

        $this->log($log);


      $cust_acc = explode("-", $customer);
      Customer::where('customer_no','=',$cust_acc[0])
                ->where('addr_code','=',$cust_acc[1])
                ->delete();

      $job = (new CustomerJob("customer",$customer));

      $this->dispatchNow($job);
    }

    public function clearCustomers($clear){
        $log = array(
            'action' => 'Clearing customers',
            'message' => 'clearing...'.print_r($clear,TRUE),
            'severity' => env('LOG_SEVERITY_ZERO')
        );

        $this->log($log);
          $log = array(
              'action' => 'USER IS AUTHENTICATED',
              'message' => 'clearing...'.print_r($clear,TRUE),
              'severity' => env('LOG_SEVERITY_ZERO')
          );

          $this->log($log);
        if($clear["rep"]){
            $log = array(
                'action' => 'CLEARING REP CUSTOMERS',
                'message' => 'CLEARING...',
                'severity' => env('LOG_SEVERITY_ZERO')
            );

            $this->log($log);
          Customer::where('salesperson_no','=',$clear["rep"])->delete();
        }
        else if($clear["accs"] != "*"){
            $log = array(
                'action' => 'CLEARING SPEC CUSTOMERS',
                'message' => 'CLEARING...',
                'severity' => env('LOG_SEVERITY_ZERO')
            );

            $this->log($log);
            $accounts = explode(",",$clear["accs"]);
            foreach($accounts as $account){
                $acc = explode("-",$account);
                Customer::where('customer_no','=',$acc[0])
                            ->where('addr_code','=',$acc[1])
                            ->delete();
            }
        }
        else{
            $log = array(
                'action' => 'CLEARING ALL',
                'message' => 'CLEARING...',
                'severity' => env('LOG_SEVERITY_ZERO')
            );

            $this->log($log);
            Customer::truncate();
        }
    }

    //ADMIN QUEUES/JOBS
    public function addCustomer($customer_deets){
      $customer = new Customer($customer_deets);
      $customer->save();

      $log = array(
        'action' => "AUTOMATED: ADDING CUSTOMER",
        'message' => $customer["customer_no"]."-".$customer["addr_code"],
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);
    }
}
