<?php

namespace App\Http\Controllers\v100;

use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use App\Http\Models\v100\CallReport\CallReport;
use App\Http\Models\v100\CallReport\NewCustomerLocal;
use App\Http\Models\v100\CallReport\NewCustomer;
use App\Http\Models\v100\CallReport\CallReportCateg;
use App\Http\Models\v100\CallReport\SellThrough;
use App\Http\Models\v100\CallReport\PictureProps;

/*
*   1. GENERATE A UNIQUE ID FOR IMAGE NAMES
*   2. GENERATE A UNIQUE VISIT ID
*   3. IF A NEW CUSTOMER
*        (A) => CREATE AND RETURN THE CUSTOMER NUMBER
*        (B) => ASSIGN THE CUSTOMER NUMBER TO THE CALL REPORT
*   4. FINALIZE THE CALL REPORT (IMAGES, ASSIGN VISIT ID)
*   5. SUBMIT THE CALL REPORT - IF SUCCESS, PROCEED
*   6. SUBMIT THE CALL REPORT CATEGORY DETAILS (BOO STYLES,PRICES,ETC) - IF SUCCESS, PROCEED
*   7. SUBMIT THE SELLTHROUGH INFORMATION
*/

class CallReports extends BaseController
{

    private $unique_id;
    private $visit_id;
    private $errors = array();

    public function submitCallReport(){
      $this->unique_id = uniqid();
      $imgManager = new ImageManager();

      $files = array();

      $data = json_decode(Input::get('json'),true);

      $this->errors = array();

      //GET VISIT ID
      $results = DB::connection('tyret')->select(DB::raw("SELECT ttcus.rep_visit_id.nextval@tycus from dual"));
      $this->visit_id = $results[0]->nextval;

      //START LOG
      $log = array(
        'action' => 'PREPARING CALL REPORT',
        'msgs' => Input::get('json'),
        'severity' => env('LOG_SEVERITY_ZERO')
      );
      $this->log($log);

      $properties = array();

      //IF NEW CUSTOMER, CREATE AND RETURN CUSTOMER NUMBER
      $customer_dets = array(
        'customer_no' => $data["call_report"]["customer_no"]
      );
      if($data["call_report"]["is_prospect"] == "Y"){
        $data["call_report"]["new_customer"]["visit_id"] = $this->visit_id;
        $customer_dets = $this->createNewCustomer($data["call_report"]["new_customer"]); //RETURNS FALSE IF FAILURE
        $data["call_report"]["customer_no"] = $customer_dets['customer_no'];
      }
      if($data["call_report"]["customer_no"] !== FALSE){
        //GRAB IMAGES
        $file = storage_path('app/').$this->visit_id.".jpg";
        $image = $imgManager->make($data["call_report"]["image"])->save($file);
        $data["call_report"]["db_image"] = file_get_contents(storage_path('app/').$this->visit_id.".jpg");
        $data["call_report"]["image_path"] = "./upload/".$this->unique_id.".jpg";

        $files[] = $file;

        if(isset($data["call_report"]["images"])){
            $dates = explode("|",$data["call_report"]["dates"]);
            $gps = explode("|",$data["call_report"]["gps"]);
          $i=1;
          foreach($data["call_report"]["images"] as $img){
            $index = $i == 1 ? "" : $i;
            $file = storage_path('app/').$this->visit_id.$index.".jpg";
            $image = $imgManager->make($img)->save($file);
            $files[] = $file;
            $data["call_report"]["db_image$index"] = file_get_contents($file);
            $data["call_report"]["image_path$index"] = "./upload/".$this->unique_id.$index.".jpg";

            $properties[$this->unique_id.$index]["GPS"] = $gps[$i];
            $properties[$this->unique_id.$index]["DATE"] = $dates[$i];
            $properties[$this->unique_id.$index]["SOURCE"] = "TYOMS";
            $i++;
          }
        }
        if($data["call_report"]["suggestedOrder"] != "0"){
            $file = storage_path('app/').$this->unique_id."suggested.jpg";
            $image = $imgManager->make($data["call_report"]["suggestedOrder"])->save($file);
            $data["call_report"]["db_image6"] = file_get_contents($file);
            $data["call_report"]["image_path6"] = "./upload/".$this->unique_id."suggested.jpg";

            $files[] = $file;
        }

        //GET DATE
        $results = DB::connection('tyret')->select(DB::raw("SELECT SYSDATE as today from dual"));
        $date = $results[0]->today;

        //ADDITIONAL CALL REPORT DETAILS
        $data["call_report"]["picture_prop"] = $this->unique_id;
        $data["call_report"]["visit_id"] = $this->visit_id;
        $data["call_report"]["timezone_date"] = $date;
        $data["call_report"]["cdate"] = $date;
        $data["call_report"]["replogin"] = $this->user["userid"];
        $data["call_report"]["repcode"] = $this->user["userid"];
        $data["call_report"]["cm_status"] = 'Y';
        $data["call_report"]["status"] = 'Y';
        $suggested_order = explode(".",$data["call_report"]["last_sug_order"]);
        $data["call_report"]["last_sug_order"] = $suggested_order[0];

        $callReport = new CallReport($data["call_report"]);
        if(!$callReport->save()){
          $this->errors[] = "FAILED TO CREATE CALL REPORT";
        }

        //REMOVE UPLOADED IMAGES FROM FILE SYSTEM
        foreach($files as $file) {
            Storage::delete($file);
        }

        if(!$this->errors){
          //CATEGORIES
          $this->categories($data["call_report"]["categories"]);

          //PROPERTIES
          if(count($properties) > 0){
              $this->properties($properties);
          }

          //SELLTHROUGH
          if($data["call_report"]["sellThrough"] != "NA"){
            $this->sellThrough($data["call_report"]["sellThrough"]);
          }

          $log["action"] = "SUBMITTING CALL REPORT";
          $log["msgs"] = "CALL REPORT SUBMITTED";

          $this->log($log);

          //RETURN POSITIVE RESPONSE
          return $this->returnData(array(
              'status' => 0,
              'message' => "CALL REPORT SUBMITTED",
              'customer_dets' => $customer_dets,
              'visit_id' => $this->visit_id
          ));
        }
        $log["action"] = "SUBMITTING CALL REPORT";
        $log["msgs"] = implode("\n",$this->errors);
        $log["severity"] = env('LOG_SEVERITY_HIGH');
        $this->log($log);
        return $this->returnData(array(
          'status' => 1,
          'message' => $this->errors
        ));
      }
      $this->errors[] = "FAILED TO CREATE CUSTOMER";

      $log["action"] = "SUBMITTING CALL REPORT";
      $log["msgs"] = implode("\n",$this->errors);
      $log["severity"] = env('LOG_SEVERITY_HIGH');

      $this->log($log);

      //RETURN FAILED RESPONSE
      return $this->returnData(array(
        'status' => 1,
        'message' => $this->errors
      ));
    }

    private function properties($properties){
        foreach($properties as $unique=>$property){
            foreach($property as $key=>$value){
                $prop = array();
                $prop["pic_id"] = $unique;
                $prop["pic_prop"] = $key;
                $prop["pic_value"] = $value;

                $picProp = new PictureProps($prop);
                $picProp->save();
            }
        }
    }

    private function categories($categs){
      foreach($categs as $categ){
        $categ["pos"] = $categ["pos"] == "(null)" ? NULL : $categ["pos"];
        $categ["picture_id"] = $this->unique_id;
        $new_categ = new CallReportCateg($categ);
        if(!$new_categ->save()){
          $this->errors[] = "FAILED TO CREATE CATEGORY";
        }
      }
    }

    private function sellThrough($sellThroughs){

      foreach($sellThroughs as $skey => $sellThrough){
        foreach($sellThrough as $key=>$array){

          if($key != "date"){
            $sell_thru = array(
              'visit_id' => $this->visit_id,
              'floor_date' => strtoupper(date('y-M-d',strtotime($sellThrough["date"]["floor_date"]))),
              'categ_code' => $key,
              'sold' => $sellThrough[$key]["sold"],
              'recd' => $sellThrough[$key]["recd"],
              'reord_ind' => isset($sellThrough[$key]["reord_ind"]) ? $sellThrough[$key]["reord_ind"] : "N"
            );

            $sellThru = new SellThrough($sell_thru);
            $sellThru->save();

            error_log("SELLTHROUGH: ".print_r($sell_thru,TRUE));
          }
        }
      }
      DB::connection('tycus')->statement(DB::raw("UPDATE ty_rep_loc_inv SET floor_date=floor_date-2 WHERE visit_id='$this->visit_id'"));
    }

    private function createNewCustomer($new_cust){

      $country_code = is_numeric($new_cust["zip"]) ? 'US' : 'CA'; //DETERMINE COUNTRY CODE
      $imgManager = new ImageManager();

      //GENERATE ADDITIONAL PROPERTIES
      $new_cust["entity"] = $country_code == "US" ? "010000" : "030000";
      $new_cust["country_code"] = $country_code;
      $new_cust["contact_ref"] = $this->user["userid"];
      $new_cust["salesperson_no"] = $this->user["userid"];
      $new_cust["region_code"] = $this->user["region"];
      $new_cust["leadsrc_type_code"] = $new_cust["lead_source_code"] == "NA" ? "REP" : "SHO";
      if($new_cust["leadsrc_type_code"] == "REP"){
        $new_cust["lead_source_code"] = "APP";
      }

      //GENERATE CUSTOMER NUMBER
      $results = DB::connection('tyret')->select(DB::raw("select 'P' || ops\$sqltime.customer_no_seq.nextval@tydb as prospect_no from dual"));
      $new_cust["customer_no"] = $results[0]->prospect_no;

      //ORGANIZE SIGNATURE
      $imgManager->make($new_cust["signature"])->save(storage_path('app/').$this->visit_id.".jpg");
      $new_cust["signature"] = "";
      $new_cust["tc_image"] = file_get_contents(storage_path('app/').$this->visit_id.".jpg");

      $new_customer = new NewCustomerLocal($new_cust);
      $new_customer->save();

      $nc = new NewCustomer($new_cust);
      if($nc->save()){
        $keys = implode(",", $new_customer->keys);

        DB::connection('tyret')->statement(DB::raw("UPDATE ct_names_tydb SET tc_image=(SELECT tc_image FROM ct_names_stage_local WHERE visit_id='$this->visit_id') WHERE visit_id='$this->visit_id'"));

        return array(
          'customer_no' => $new_cust["customer_no"],
          'country_code' => $country_code
        );
      }
      return FALSE;

    }
}
