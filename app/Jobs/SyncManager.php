<?php

namespace App\Jobs;
use App\Jobs\EmailManager;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\v100\Customers;
use App\Http\Controllers\v100\Items;
use App\Http\Controllers\v100\Kits;
use App\Http\Controllers\v100\Discounts;
use App\Http\Models\v100\Pcc;
use App\Http\Models\v100\Log;
use App\Http\Models\v100\Customer;
use Carbon\Carbon;

class SyncManager extends Job{

  private $option;
  private $rep;
  private $params;

  public function __construct($option,$rep=FALSE,$params=FALSE){
    $this->option = $option;
    $this->rep = $rep;
    $this->params = $params;
  }

  public function handle(){
    switch($this->option){
      case "customers":
        $kit_item_nos = $this->syncKits();
        $this->syncCustomers($kit_item_nos);
      break;
      case "changedCustomers":
        $kit_item_nos = $this->syncKits();
        $this->changedCustomers($kit_item_nos);
      break;
      case "items": $this->syncItems();
      break;
      case "discounts": $this->syncDiscounts();
      break;
      case "logs": $this->getLogs();
      break;
      case "cleanup": $this->cleanup();
      break;
    }
  }

  private function cleanUp(){
    Log::where('created_at', '>', Carbon::now()->subDays(7))->delete();
  }

  private function getLogs(){
    if(!$this->rep){ //HOURLY
      //HOURLY QUERY
      if($this->params["logType"] == "ALERTSANDWARNINGS"){
        $this->params["email"] = "scoleman@redacted.com";

        $subject = "HOURLY LOGS";
        $message = "WARNINGS AND ERRORS";
        $date = new \DateTime();
        $date->modify('-1 hours');
        $formatted_date = $date->format('Y-m-d H:i:s');

        $logs = Log::where('severity','>=','1')
                    ->where('created_at', '>', Carbon::now()->subHour())
                    ->get();
      }
      else{
        //GET EVERYTHING IN LAST 24 HOURS
        $this->params["email"] = isset($this->params["email"]) ? $this->params["email"] : "scoleman@redacted.com";

        $subject = "24 HOUR LOGS";
        $message = "LOGS IN PAST 24 HOURS";

        $date = new \DateTime();
        $date->modify('-24 hours');
        $formatted_date = $date->format('Y-m-d H:i:s');

        $logs = Log::where('created_at', '>', Carbon::now()->subDays(1))
                    ->get();
      }

    }
    else{ //LOGS FOR REP TODAY
      //REP TODAY
      $this->params["email"] = isset($this->params["email"]) ? $this->params["email"] : "scoleman@redacted.com";

      $subject = "LOGS: ".$this->rep;
      $message = "TODAYS LOGS FOR ".$this->rep;

      $date = new \DateTime();
      $date->modify('-12 hours');
      $formatted_date = $date->format('Y-m-d H:i:s');
      $logs = Log::where('userid','=',$this->rep)
                  ->where('created_at', '>', Carbon::now()->subHours(12))
                  ->get();
    }

    $job = (new EmailManager(
      array(
        'to'=>$this->params["email"],
        'subject'=>$subject,
        'title' => $message
      ),
      $logs,
      3
    ));

    dispatch($job);
  }

  private function syncDiscounts(){
    $discountController = new Discounts();
//    $discountController->clearDiscounts();

    $time_start = time();
    $discounts = array();

    $discounts ["cart"] ["GuaranteeProgram"] = "";

    $discounts ["cart"] ["FreightDiscount"]["US"] = 1000;
    $discounts ["cart"] ["FreightDiscount"]["CA"] = 1500;

    $discounts ["cart"] ["FreeFreight"]["US"] = 1500;
    $discounts ["cart"] ["FreeFreight"]["CA"] = 2000;

    $discounts ["cart"] ["Hardcode"]["US"] = 3000;
    $discounts ["cart"] ["Hardcode"]["CA"] = 3500;

    $discounts ["cart"] ["ProgramDiscount"]["US"] = 1000;
    $discounts ["cart"] ["ProgramDiscount"]["CA"] = 1500;

    $discounts ["cart"] ["minAmt"]["US"] = 200;
    $discounts ["cart"] ["minAmt"]["CA"] = 250;

    $discounts ["cart"] ["resetDate"] = "2016-01-04";

    $discounts ["cart"] ["itemExceptions"][0]["item"] = "42308";
    $discounts ["cart"] ["itemExceptions"][0]["amount"] = 63;

    $discounts ["cartExceptions"][0]["customer_no"] = "112999";
    $discounts ["cartExceptions"][0]["amount"] = 100;

    $discounts ["cartExceptions"][1]["customer_no"] = "119890";
    $discounts ["cartExceptions"][1]["amount"] = 100;

    $discounts ["cartExceptions"][2]["customer_no"] = "120646";
    $discounts ["cartExceptions"][2]["amount"] = 100;

    $discounts ["cartExceptions"][3]["customer_no"] = "120436";
    $discounts ["cartExceptions"][3]["amount"] = 100;

    $discounts ["cartExceptions"][4]["customer_no"] = "120960";
    $discounts ["cartExceptions"][4]["amount"] = 100;

    $discounts ["cartExceptions"][5]["customer_no"] = "120902";
    $discounts ["cartExceptions"][5]["amount"] = 100;

    $discounts ["cartExceptions"][6]["customer_no"] = "118277";
    $discounts ["cartExceptions"][6]["amount"] = 100;

    $discounts ["cartExceptions"][7]["customer_no"] = "121182";
    $discounts ["cartExceptions"][7]["amount"] = 100;

    $discounts ["cartExceptions"][8]["customer_no"] = "121264";
    $discounts ["cartExceptions"][8]["amount"] = 100;

    $discounts ["cartExceptions"][9]["customer_no"] = "121181";
    $discounts ["cartExceptions"][9]["amount"] = 100;

    $discounts ["cartExceptions"][10]["customer_no"] = "120325";
    $discounts ["cartExceptions"][10]["amount"] = 100;

    $discounts ["cartExceptions"][11]["customer_no"] = "121362";
    $discounts ["cartExceptions"][11]["amount"] = 100;

    $discounts ["cartExceptions"][12]["customer_no"] = "123003";
    $discounts ["cartExceptions"][12]["amount"] = 100;

    $discounts ["cartExceptions"][13]["customer_no"] = "122499";
    $discounts ["cartExceptions"][13]["amount"] = 100;

    $discounts ["cartExceptions"][14]["customer_no"] = "122358";
    $discounts ["cartExceptions"][14]["amount"] = 100;

    $discounts ["cartExceptions"][15]["customer_no"] = "115230";
    $discounts ["cartExceptions"][15]["amount"] = 100;

    $discounts ["cartExceptions"][16]["customer_no"] = "123346";
    $discounts ["cartExceptions"][16]["amount"] = 100;

    $discounts ["cartExceptions"][17]["customer_no"] = "123364";
    $discounts ["cartExceptions"][17]["amount"] = 100;

    $discounts ["cartExceptions"][18]["customer_no"] = "123365";
    $discounts ["cartExceptions"][18]["amount"] = 100;

    $discounts ["cartExceptions"][19]["customer_no"] = "123367";
    $discounts ["cartExceptions"][19]["amount"] = 100;

    $discounts ["cartExceptions"][20]["customer_no"] = "123368";
    $discounts ["cartExceptions"][20]["amount"] = 100;

    $discounts ["cartExceptions"][21]["customer_no"] = "123369";
    $discounts ["cartExceptions"][21]["amount"] = 100;

    $discounts ["cartExceptions"][22]["customer_no"] = "123449";
    $discounts ["cartExceptions"][22]["amount"] = 100;

    $discounts ["cartExceptions"][23]["customer_no"] = "123441";
    $discounts ["cartExceptions"][23]["amount"] = 100;

    $discounts ["cartExceptions"][24]["customer_no"] = "123442";
    $discounts ["cartExceptions"][24]["amount"] = 100;

    $discounts ["cartExceptions"][25]["customer_no"] = "115097";
    $discounts ["cartExceptions"][25]["amount"] = 100;

    //ADD EXCEPTION FOR ALL CVS ACCOUNTS *AND* FOR ALL ACCOUNTS WITH SIC_CODE OF RITEAD

    $results = DB::connection('tyret')->select(DB::raw("SELECT customer_no FROM ar_customer_master@tydb WHERE (des1 LIKE '%CVS%') OR sic_code IN ('RITEAD','7ELEVN')"));

    $i = 26;

    foreach($results as $result)
    {
      $discounts['cartExceptions'][$i]["customer_no"] = $result->customer_no;
      $discounts['cartExceptions'][$i]["amount"] = 100;
      $i++;
    }
    $discountController->addDiscount($discounts);

    $time_end = time();

    $job = (new EmailManager(
      array(
        'to'=>'scoleman@redacted.com',
        'subject'=>'Discounts Sync Complete',
        'message'=>"Total Recs: ".count($discounts["cartExceptions"])." <br />".date("H:i:s",$time_end - $time_start),
        'title'=>'Discount Sync'
      )
    ));

    dispatch($job);
  }

  private function syncKits(){
    $kitController = new Kits();
    $kitController->clearKits();
    $results = DB::connection('tyret')->select(DB::raw("SELECT * FROM tr_api_kits WHERE kit_ispublished='Y'"));


    $item_kit_nos = array();
    $kit_exceptions = array();

    foreach($results as $result){
      $kit = array();
      $kit["name"] = $result->kit_name;
      $kit["item_no"] = $result->kit_itemno;
      $kit["items"] = explode(" ",$result->kit_items);
      $kit["is_exception"] = $result->kit_isexception;
      $kit["exception_items"] = explode(" ",$result->kit_exceptionitems);

      $kit_item_nos = explode("|",$result->kit_itemno);
      foreach($kit_item_nos as $kit_item_no){
        $item_kit_nos[] = "'".$kit_item_no."'";
      }
      $kitController = new Kits();
      $kitController->addKit($kit);
    }

    return $item_kit_nos;
  }

  private function syncItems(){
    $time_start = time();
    $status=1;

    DB::connection('tyret')->statement(DB::raw("alter session set nls_date_format = 'MM/DD/YYYY HH24:MI'"));

    //FIRST - GATHER ARRAY OF ITEM NUMBERS NOT IN OTHER
    //ANYTHING NOT IN TR_OFRM_LINE FALLS INTO OTHER

    $is_not_other = array();

    $results = DB::connection('tyret')->select(DB::raw("SELECT items FROM tr_ofrm_line WHERE start_date < sysdate AND end_date > sysdate"));
    foreach($results as $line){
      $item_list = explode("|", $line->items);
      foreach($item_list as $il){
        $is_not_other[] = $il;
      }
    }

    //FETCH ITEMS

    $results = DB::connection('tyret')->select(DB::raw("select A.item_no, A.item_des, A.item_min_qty_ae, A.item_max_qty_ae, A.item_default_qty_ae,
                A.item_cost, A.min_required, A.include_customers, A.exclude_customers, A.commodity_code_real, A.include_state, A.exclude_state,
                A.include_leadcodes, A.exclude_leadcodes, A.include_territory, A.exclude_territory, A.include_sic, A.exclude_sic, A.include_group_codes, A.item_notes,
                  A.exclude_group_codes,'https://tycdn.azureedge.net/static/images/'||I.image_hash||'_lg.'||I.image_ext as lg_img, A.country_code , A.pcc
                  from pcc B, item_master A left outer join im_item_master I on A.item_no=I.item_no
                  where A.pcc IN (SELECT pcc FROM pcc WHERE allow_order='y' AND pcc_livedate <= SYSDATE AND pcc_deaddate >= SYSDATE)
                  and A.country_code=B.country_code and A.commodity_code_real=B.pcc and
                   (A.item_livedate = '01/01/1900 00:00' or A.item_livedate <= SYSDATE) and
                  (A.item_deaddate = '01/01/1900 00:00' or A.item_deaddate >= SYSDATE) and A.allow_order = 'y'
                order by B.sortorder, A.commodity_code_real, A.sort_order, A.item_cost, A.item_no, 10"));

    $item_count = count($results);

    foreach($results as $result){
      $item_obj = array();
      foreach($result as $key=>$val){
        if(!is_numeric($key)){
          if($key == "item_no"){
            //CHECK IF THIS ITEM IS NOT OTHER
            if(!in_array($val, $is_not_other)){
              $item_obj["is_other"] = "Y";
            }
          }
          $item_obj[strtolower($key)] = $val === NULL ? "" : $val;
        }
      }
      $itemController = new Items();
      $itemController->addItem($item_obj);
      $status = 0;
    }
    $time_end = time();

    $job = (new EmailManager(
      array(
        'to'=>'scoleman@redacted.com',
        'subject'=>'Item Sync Complete',
        'message'=>"Total Recs: ".count($results)." <br />".date("H:i:s",$time_end - $time_start)
      )
    ));

    dispatch($job);
  }

  private function changedCustomers($kit_item_nos)
  {
      $interval = "6/1440";

      $results = DB::connection('tyret')->select(DB::raw("SELECT
             customer_no,
            addr_code
            FROM ar_cust_addr_code@tydb
            WHERE (customer_no,addr_code) in
              (SELECT customer_no, addr_code
               FROM diff_columns@tydb
               WHERE chg_date_time >= sysdate-($interval)
                AND chg_username != 'EDIADMIN'
              UNION
               SELECT customer_no, addr_code
               FROM ar_cust_addr_code_chgs@tydb
               WHERE chg_date_time >= sysdate-($interval)
                AND chg_type='I'
                AND image_type='A'
                AND chg_username != 'EDIADMIN'
               )
            UNION
            SELECT customer_no,addr_code FROM credit_data
            WHERE
            date_created >= sysdate-($interval)
            OR date_modified >= sysdate-($interval)"));

      foreach($results as $result){
        Customer::where('customer_no','=',$result->customer_no)
                  ->where('addr_code','=',$result->addr_code)
                  ->delete();
        $this->params["customer"][0] = $result->customer_no;
        $this->params["customer"][1] = $result->addr_code;

        $this->syncCustomers($kit_item_nos);
      }
  }

  private function syncCustomers($kit_item_nos){
    $time_start = time();

    $purchkits = "'".implode("','",$kit_item_nos)."'";

    if(!$this->params){

      if(!$this->rep){
        //GET REPS WHOSE CUSTOMERS WE NEED
        $reps = DB::connection('tyret')->select(DB::raw("SELECT B.salesperson_no FROM ar_salesperson_code@tydb A, tr_api_ipad_tracker B
                  WHERE A.salesperson_no=B.salesperson_no
                  AND A.end_date IS NULL
                  AND A.inactive_flag = 'N'
                  AND B.has_ipad = 'Y'"));

        $repstring = "";
        foreach($reps as $rep){
          $rep_no = $rep->salesperson_no;
          $repstring .= "'$rep_no',";
        }

        $repstring = substr($repstring, 0, -1);

      }
      else{
        $repstring = "'$this->rep'";
      }

      error_log("REPSTRING: ".$repstring);
      error_log("PURCHKITS: ".$purchkits);

      //GET CREDIT CARD DATA
      $cc_data = $this->get_cc_data($repstring);

      //GET A RECORD OF CUSTOMERS THAT HAVE PURCHASED THE ACTIVE KITS BY THE ACTIVE REPS
      $results = DB::connection('tyret')->select(DB::raw("select a.customer_no, a.addr_code, b.item_no
      from im_item_master b, ar_cust_addr_code a
      where a.salesperson_no in ($repstring)
      and nvl(b.include_in_retailer_vw,'N') = 'Y'
      and b.item_no IN ($purchkits)
      and retailers_kit_order_fn(a.customer_no, a.addr_code, b.item_no) = 'Y'"));
    }
    else{
      //GET CREDIT CARD DATA
      $cc_data = $this->get_cc_data();

      $results = DB::connection('tyret')->select(DB::raw("select a.customer_no, a.addr_code, b.item_no
      from im_item_master b, ar_cust_addr_code a
      WHERE
      nvl(b.include_in_retailer_vw,'N') = 'Y'
      and b.item_no IN ($purchkits)
      and a.customer_no='".$this->params["customer"][0]."'
      and a.addr_code='".$this->params["customer"][1]."'
      and retailers_kit_order_fn(a.customer_no, a.addr_code, b.item_no) = 'Y'"));
    }

   $has_kits = array();
   foreach($results as $result){
     $has_kits["$result->customer_no-$result->addr_code"][] = $result->item_no;
   }

   if(!$this->params){
     //GET CUSTOMERS
     if(!$this->rep){
       echo "GET CUSTOMERS 1 - EVERYTHING";
       $results = DB::connection('tyret')->select(DB::raw("select
             B.customer_no,B.addr_code,B.des1,B.addr1,B.addr2,B.city,B.state_code,B.zip,B.main_phone_no,A.terms_code,B.last_visit_date, A.entity, B.ud_note, B.ud_note7, B.ud_note6,
             A.cust_type_code, A.allow_backorders,A.status as master_status, A.main_contact as bill_main_contact,A.des1 as billing_name, A.addr1 as billing_addr1,A.city as billing_city,A.state_code as billing_state,A.country_code as billing_country,A.zip as billing_zip, A.email_addr as bill_email,
             B.cust_loc_store_code,B.status as status, A.credit_limit,
             A.terr_code, A.sic_code, B.email_addr as email_addr_2, A.multi_loc_qty, B.cust_div_code, A.cust_group_code as mcust_group_code, B.bill_parent_flag, B.main_contact,
             B.cust_group_code as cust_group_code, b.inside_salesperson_no,B.network_group,B.salesperson_no,B.no_pict_req,B.block_order_flag,B.look_book_flag, B.last_ship_date,
             B.ship_method_code,B.latitude,B.longitude, B.price_adj_code, B.country_code, B.disable_combined_genpick_flag AS disable_combine, (SELECT DISTINCT(C.note) FROM ttcus.ty_cust_notes@tycus C
             WHERE C.customer_no=B.customer_no AND C.addr_code = B.addr_code AND status='N' AND rownum=1) AS notes,
             D.return_message as info,
                     A.po_required_flag,
                     to_char(decode(b.status,'M',a.last_ord_date,b.last_ord_date),'fmMM/fmDD') last_ord_date,
                     to_char(decode(b.status,'M',a.last_ord_date,b.last_ord_date),'YYYY') last_ord_year,
                     nvl(b.pct_ty,0) prev_pct_ty,
                     nvl(b.display_type1,0) display_type1,nvl(b.display_type2,0) display_type2,nvl(b.display_type3,0) display_type3,nvl(b.display_type4,0) display_type4,
                     nvl(a.ud_note2,0) ud_note2,
                    val_ind,eas_ind,hwn_ind,chr_ind
                     from ar_customer_master_tydb A, ar_cust_addr_code_tydb B, ioe_addr_code_notify C, ty_customer_info_snap D
             where A.customer_no=B.customer_no
             AND A.customer_no=D.customer_no
             AND B.addr_code = D.addr_code and B.status in ('I','A','M') and A.status <> 'C' and B.addr_code != 'SAMPLE' and B.addr_code != 'MAP'
            AND B.customer_no=C.customer_no AND B.addr_code = C.addr_code
                     AND B.salesperson_no IN ($repstring)
                     union select
                  B.customer_no,B.addr_code,B.des1,B.addr1,B.addr2,B.city,B.state_code,B.zip,B.main_phone_no,A.terms_code,B.last_visit_date, A.entity, B.ud_note, B.ud_note7, B.ud_note6,
                     A.cust_type_code, A.allow_backorders,A.status as master_status, A.main_contact as bill_main_contact,A.des1 as billing_name, A.addr1 as billing_addr1,A.city as billing_city,A.state_code as billing_state,A.country_code as billing_country,A.zip as billing_zip, A.email_addr as bill_email,
                     B.cust_loc_store_code,B.status as status, A.credit_limit,
                     A.terr_code, A.sic_code, B.email_addr as email_addr_2, A.multi_loc_qty, B.cust_div_code, A.cust_group_code as mcust_group_code, B.bill_parent_flag, B.main_contact,
                     B.cust_group_code as cust_group_code, b.inside_salesperson_no,B.network_group,B.salesperson_no,B.no_pict_req,B.block_order_flag,B.look_book_flag, B.last_ship_date,
                     B.ship_method_code,B.latitude,B.longitude, B.price_adj_code, B.country_code, B.disable_combined_genpick_flag AS disable_combine, (SELECT DISTINCT(C.note) FROM ttcus.ty_cust_notes@tycus C
                     WHERE C.customer_no=B.customer_no AND C.addr_code = B.addr_code AND status='N' AND rownum=1) AS notes,
                     D.return_message as info,
                             A.po_required_flag,
                             to_char(decode(b.status,'M',a.last_ord_date,b.last_ord_date),'fmMM/fmDD') last_ord_date,
                             to_char(decode(b.status,'M',a.last_ord_date,b.last_ord_date),'YYYY') last_ord_year,
                             nvl(b.pct_ty,0) prev_pct_ty,
                             nvl(b.display_type1,0) display_type1,nvl(b.display_type2,0) display_type2,nvl(b.display_type3,0) display_type3,nvl(b.display_type4,0) display_type4,
                             nvl(a.ud_note2,0) ud_note2,
                            val_ind,eas_ind,hwn_ind,chr_ind
                          from ar_customer_master_tydb A, ar_cust_addr_code_tydb B, ioe_addr_code_notify C, ty_customer_info_snap D
                     where A.customer_no=B.customer_no
                     AND A.customer_no=D.customer_no
                     AND B.addr_code = D.addr_code and B.cust_div_code = 'FLD'
                     AND
                     B.status IN ('I','A')
                     AND
                     B.salesperson_no NOT IN ($repstring)
                    AND B.customer_no=C.customer_no AND B.addr_code = C.addr_code"));
          }
          else{
            echo "GET CUSTOMERS 2 - SPECIFIC REP $repstring";
            $results = DB::connection('tyret')->select(DB::raw("select
                  B.customer_no,B.addr_code,B.des1,B.addr1,B.addr2,B.city,B.state_code,B.zip,B.main_phone_no,A.terms_code,B.last_visit_date, A.entity, B.ud_note, B.ud_note7, B.ud_note6,
                  A.cust_type_code, A.allow_backorders,A.status as master_status, A.main_contact as bill_main_contact,A.des1 as billing_name, A.addr1 as billing_addr1,A.city as billing_city,A.state_code as billing_state,A.country_code as billing_country,A.zip as billing_zip, A.email_addr as bill_email,
                  B.cust_loc_store_code,B.status as status, A.credit_limit,
                  A.terr_code, A.sic_code, B.email_addr as email_addr_2, A.multi_loc_qty, B.cust_div_code, A.cust_group_code as mcust_group_code, B.bill_parent_flag, B.main_contact,
                  B.cust_group_code as cust_group_code, b.inside_salesperson_no,B.network_group,B.salesperson_no,B.no_pict_req,B.block_order_flag,B.look_book_flag, B.last_ship_date,
                  B.ship_method_code,B.latitude,B.longitude, B.price_adj_code, B.country_code, B.disable_combined_genpick_flag AS disable_combine, (SELECT DISTINCT(C.note) FROM ttcus.ty_cust_notes@tycus C
                  WHERE C.customer_no=B.customer_no AND C.addr_code = B.addr_code AND status='N' AND rownum=1) AS notes,
                  D.return_message as info,
                          A.po_required_flag,
                          to_char(decode(b.status,'M',a.last_ord_date,b.last_ord_date),'fmMM/fmDD') last_ord_date,
                          to_char(decode(b.status,'M',a.last_ord_date,b.last_ord_date),'YYYY') last_ord_year,
                          nvl(b.pct_ty,0) prev_pct_ty,
                          nvl(b.display_type1,0) display_type1,nvl(b.display_type2,0) display_type2,nvl(b.display_type3,0) display_type3,nvl(b.display_type4,0) display_type4,
                          nvl(a.ud_note2,0) ud_note2,
                         val_ind,eas_ind,hwn_ind,chr_ind
                          from ar_customer_master_tydb A, ar_cust_addr_code_tydb B, ioe_addr_code_notify C, ty_customer_info_snap D
                  where A.customer_no=B.customer_no
                  AND A.customer_no=D.customer_no
                  AND B.addr_code = D.addr_code and B.status in ('I','A','M') and A.status <> 'C' and B.addr_code != 'SAMPLE' and B.addr_code != 'MAP'
                 AND B.customer_no=C.customer_no AND B.addr_code = C.addr_code
                          AND B.salesperson_no IN ($repstring)"));
          }
       }
       else{
         echo "GET CUSTOMERS 3 - SPECIFIC CUSTOMER";
         $results = DB::connection('tyret')->select(DB::raw("select
               B.customer_no,B.addr_code,B.des1,B.addr1,B.addr2,B.city,B.state_code,B.zip,B.main_phone_no,A.terms_code,B.last_visit_date, A.entity, B.ud_note, B.ud_note7, B.ud_note6,
               A.cust_type_code, A.allow_backorders,A.status as master_status, A.main_contact as bill_main_contact,A.des1 as billing_name, A.addr1 as billing_addr1,A.city as billing_city,A.state_code as billing_state,A.country_code as billing_country,A.zip as billing_zip, A.email_addr as bill_email,
               B.cust_loc_store_code,B.status as status, A.credit_limit,
               A.terr_code, A.sic_code, B.email_addr as email_addr_2, A.multi_loc_qty, B.cust_div_code, A.cust_group_code as mcust_group_code, B.bill_parent_flag, B.main_contact,
               B.cust_group_code as cust_group_code, b.inside_salesperson_no,B.network_group,B.salesperson_no,B.no_pict_req,B.block_order_flag,B.look_book_flag, B.last_ship_date,
               B.ship_method_code,B.latitude,B.longitude, B.price_adj_code, B.country_code, B.disable_combined_genpick_flag AS disable_combine, (SELECT DISTINCT(C.note) FROM ttcus.ty_cust_notes@tycus C
               WHERE C.customer_no=B.customer_no AND C.addr_code = B.addr_code AND status='N' AND rownum=1) AS notes,
               D.return_message as info,
                       A.po_required_flag,
                       to_char(decode(b.status,'M',a.last_ord_date,b.last_ord_date),'fmMM/fmDD') last_ord_date,
                       to_char(decode(b.status,'M',a.last_ord_date,b.last_ord_date),'YYYY') last_ord_year,
                       nvl(b.pct_ty,0) prev_pct_ty,
                       nvl(b.display_type1,0) display_type1,nvl(b.display_type2,0) display_type2,nvl(b.display_type3,0) display_type3,nvl(b.display_type4,0) display_type4,
                       nvl(a.ud_note2,0) ud_note2,
                      val_ind,eas_ind,hwn_ind,chr_ind
                       from ar_customer_master_tydb A, ar_cust_addr_code_tydb B, ioe_addr_code_notify C, ty_customer_info_snap D
               where A.customer_no=B.customer_no
               AND A.customer_no=D.customer_no
               AND B.addr_code = D.addr_code and B.status in ('I','A','M') and A.status <> 'C' and B.addr_code != 'SAMPLE' and B.addr_code != 'MAP'
              AND B.customer_no=C.customer_no AND B.addr_code = C.addr_code
                       AND B.customer_no='".$this->params["customer"][0]."'
                       and B.addr_code='".$this->params["customer"][1]."'"));
       }
       Customer::truncate();
         foreach($results as $result){
           $customer_deets = array();
           foreach($result as $key=>$val){
             $customer_deets["$key"] = $val;
           }
           $customer_deets["addtnl_info"] = $this->resolveUDNote2($result->ud_note2,$result->customer_no,$result->addr_code);

           $cclkup = $customer_deets["customer_no"] . '_' . $customer_deets["addr_code"];

           if (isset ( $cc_data [$cclkup] )) {
             $customer_deets["credit_cards"] = $cc_data[$cclkup];
           } else {
             $customer_deets["credit_cards"] = array ();
           }
           $salesperson = DB::connection('tyret')->select(DB::raw("SELECT * FROM ar_salesperson_code@tydb WHERE salesperson_no='$result->salesperson_no'"));
           $customer_deets["pccs"] = $this->customFilterPCC($result,$salesperson[0]);
           $customer_deets["has_kits"] = isset($has_kits["$result->customer_no-$result->addr_code"]) ? $has_kits["$result->customer_no-$result->addr_code"] : NULL;

           $customersController = new Customers();
           $customersController->addCustomer($customer_deets);

          }
          $noun = $this->params ? $this->params["customer"][0]."-".$this->params["customer"][1] : $repstring;
          $time_end = time();
          $job = (new EmailManager(
            array(
              'to'=>'scoleman@redacted.com',
              'subject'=>'Customer Sync Complete',
              'message'=>"Sync for $noun complete. Total Recs: ".count($results)." <br />".date("H:i:s",$time_end - $time_start)."<br />",
              'title'=>'Customer Sync'
            )
          ));

          dispatch($job);

  }

  private function get_cc_data($reps=FALSE){

    $credit_card_data = array();

    if(!$this->params){
      $results = DB::connection('tyret')->select(DB::raw("select customer_no||'_'||addr_code as customer,last4,card_exp from credit_data where
    customer_no||'_'||addr_code in (
        select customer_no||'_'||addr_code from ar_cust_addr_code_tydb where
        status in ('A','I')
        AND salesperson_no IN ($reps)
        )
        order by customer_no, addr_code"));
      }
      else{
        $results = DB::connection('tyret')->select(DB::raw("select customer_no||'_'||addr_code as customer,last4,card_exp from credit_data where
      customer_no||'_'||addr_code in (
          select customer_no||'_'||addr_code from ar_cust_addr_code_tydb where
          status in ('A','I')
          AND customer_no='".$this->params["customer"][0]."'
          and addr_code='".$this->params["customer"][1]."'
          )
          order by customer_no, addr_code"));
      }

      foreach($results as $result) {

        $card_month = substr ( $result->card_exp, 0, 2 );
        $card_year = substr ( $result->card_exp, 2, 2 );
        $local_card_exp = mktime ( 0, 0, 0, intval ( $card_month ) + 1, 1, intval ( $card_year ) + 2000 );
        if ($local_card_exp > time ()) {
          if (isset ( $credit_card_data [$result->customer] )) {
            $credit_card_data [$result->customer] [] = array (
                'last4' => $result->last4,
                'card_exp' => $result->card_exp
            );
          } else {
            $credit_card_data [$result->customer] = array ();
            $credit_card_data [$result->customer] [] = array (
                'last4' => $result->last4,
                'card_exp' => $result->card_exp
            );
          }
        }
      }
      return $credit_card_data;
  }

  private function resolveUDNote2($ud_note2,$customer_no,$addr_code){
    $results = DB::connection('tycus')->select(DB::raw("select nvl(trunc(dol_ord_ly_ytd),0) AS v_lytd,nvl(trunc(dol_ord_ytd),0) AS v_tytd,trunc(dol_ord_ly) AS v_lyr, (case when round(pct_yy,2) < 20 then 20 else 100 end) AS v_acct_type,
            reg_rank AS v_reg_rank,nvl(trunc(DOL_ORD_LY_YTD_PROD01),0) AS v_prod1_lytd,nvl(trunc(DOL_ORD_YTD_PROD01),0) AS v_prod1_tytd
                from MSR_LOC_DOL_ORD
                where customer_no = '$customer_no'
                and addr_code= '$addr_code'"));

    if($ud_note2 == 1){
      $results = DB::connection('tycus')->select(DB::raw("select nvl(trunc(dol_ord_ly_ytd),0) AS v_lytd,nvl(trunc(dol_ord_ytd),0) AS v_tytd,trunc(dol_ord_ly) AS v_lyr, (case when round(pct_yy,2) < 20 then 20 else 100 end) AS v_acct_type,
            reg_rank AS v_reg_rank,nvl(trunc(DOL_ORD_LY_YTD_PROD01),0) AS v_prod1_lytd,nvl(trunc(DOL_ORD_YTD_PROD01),0) AS v_prod1_tytd
      from MSR_ACCT_DOL_ORD
      where customer_no = '$customer_no'"));
    }

    $addtnl_info = array();
    foreach($results as $row){
      $addtnl_info = array();
      foreach($row as $key=>$val){
        $addtnl_info["$key"] = $val;
      }
    }

    return $addtnl_info;
  }

  private function customFilterPCC($customer,$salesperson){
    $pccs = array();
    $shouldAdd = 1;

    $country_code = $customer->country_code;
    $current_date = date("m/d/Y");
    $current_month = date("m/01/Y");

    DB::connection('tyret')->statement(DB::raw("alter session set nls_date_format = 'MM/DD/YYYY HH24:MI:SS'"));

    $results = DB::connection('tyret')->select(DB::raw("select pcc, pcc_des, include_customers, exclude_customers, include_div_codes, exclude_div_codes, include_leadcodes, exclude_leadcodes, include_territory, exclude_territory, include_sic, exclude_sic,
          include_group_codes, exclude_group_codes,
          orders_per_month, orders_per_month_ae from pcc where
          country_code = '$country_code' and allow_order = 'y' and pcc_livedate <= '$current_date'
          and pcc_deaddate >= '$current_date' order by sortorder, pcc_des"));

    foreach($results as $result){
      $include_div_codes = explode("|",$result->include_div_codes);
      $exclude_div_codes = explode("|",$result->exclude_div_codes);
      $include_customers	= 	explode("|", $result->include_customers);
      $include_leadcodes	= 	explode("|", $result->include_leadcodes);
      $include_territory	= 	explode("|", $result->include_territory);
      $include_sic		=	explode("|", $result->include_sic);
      $include_groupcodes	=	explode("|", $result->include_group_codes);

      $exclude_customers	= 	explode("|", $result->exclude_customers);
      $exclude_leadcodes	= 	explode("|", $result->exclude_leadcodes);
      $exclude_territory	= 	explode("|", $result->exclude_territory);
      $exclude_sic		=	explode("|", $result->exclude_sic);
      $exclude_groupcodes	=	explode("|", $result->exclude_group_codes);

      if(!$customer->cust_group_code)
      {
        $customer->cust_group_code = "NULL";
      }
      if(!$customer->cust_type_code)
      {
        $customer->cust_type_code = "NULL";
      }
      if(!$customer->sic_code)
      {
        $customer->sic_code = "NULL";
      }

      //CHECK DIVS
      if(in_array("ALL", $exclude_div_codes) || in_array($customer->cust_div_code, $exclude_div_codes))
      {
        $shouldAdd = 0;
        if(in_array($customer->cust_div_code, $include_div_codes))
        {
          $shouldAdd = 1;
        }
      }

      //CHECK LEADCODES
      if ((in_array("ALL", $exclude_leadcodes) || in_array($salesperson->salesperson_type, $exclude_leadcodes)) && $shouldAdd == 1) {
        $shouldAdd=0;
        if(in_array($salesperson->salesperson_type, $include_leadcodes)){
          $shouldAdd=1;
        }
      }

      //CHECK TERRITORY
      elseif ((in_array("ALL", $exclude_territory) || in_array($salesperson->terr_code, $exclude_territory)) && $shouldAdd == 1) {
        $shouldAdd = 0;
        if (in_array($salesperson->terr_code, $include_territory)) {
          $shouldAdd = 1;
        }
      }

      //CHECK SIC CODE
      if ((in_array("ALL", $exclude_sic) || in_array($customer->sic_code, $exclude_sic)) && $shouldAdd == 1) {
        $shouldAdd = 0;
        if (in_array($customer->sic_code, $include_sic)) {
          $shouldAdd = 1;
        }
      }

      //CHECK GROUP CODE
      if ((in_array("ALL", $exclude_groupcodes) || in_array($customer->cust_group_code, $exclude_groupcodes)) && $shouldAdd == 1) {
        $shouldAdd = 0;
        if (in_array($customer->cust_group_code, $include_groupcodes)) {
          $shouldAdd = 1;
        }
      }

      if($shouldAdd == 1)
      {
        $pccs[] = $result->pcc;
      }
    }
    return $pccs;
  }


}

?>
