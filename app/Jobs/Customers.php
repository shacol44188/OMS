<?php

namespace App\Jobs;

use App\Jobs\MasterJob;
use Illuminate\Support\Facades\DB;
use App\Http\Models\v100\Customer;
use App\Http\Controllers\v100\Customers as CustomerController;
use App\Http\Controllers\v100\Kits;

class Customers extends MasterJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $option;
    private $param;

    public function __construct($option,$param=FALSE)
    {
        //
        $this->option = $option;
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $customerController = new CustomerController();

        $this->init($this->option);

        $time_start = time();
        $kit_item_nos = $this->syncKits();
        $purchkits = "'".implode("','",$kit_item_nos)."'";

        $query = FALSE;
        $clear = array('rep'=>FALSE,'accs'=>'*');
        $repstring = "";

        switch($this->option) {
            case 'customers': //COULD BE ALL OR REP
                if (!$this->param) {
                    $repstring = $this->getRepString();
                    $query = $this->getAllCustsQuery($repstring);
                    Customer::truncate();
                } else {
                    $repstring = $this->param;
                    $query = $this->getCustRepQuery($repstring);
                    $clear = array('rep'=>TRUE,'accs'=>'*');
                    Customer::where('salesperson_no','=',$repstring)->delete();
                }
                $cc_data = $this->get_cc_data($repstring);
                $kits_purchased = DB::connection('tyret')->select(
                    DB::raw("select a.customer_no, a.addr_code, b.item_no
                                                from im_item_master b, ar_cust_addr_code a
                                                where a.salesperson_no in ($repstring)
                                                and nvl(b.include_in_retailer_vw,'N') = 'Y'
                                                and b.item_no IN ($purchkits)
                                                and retailers_kit_order_fn(a.customer_no, a.addr_code, b.item_no) = 'Y'"));
                break;
            case 'customer': //SPECIFIC CUSTOMER
                $acc = explode("-", $this->param);
                $query = $this->getCustSpecQuery($acc);
                error_log($query);
                $cc_data = $this->get_cc_data(FALSE, $acc);
                $kits_purchased = DB::connection('tyret')->select(
                    DB::raw("select a.customer_no, a.addr_code, b.item_no
                                             from im_item_master b, ar_cust_addr_code a
                                             WHERE
                                             nvl(b.include_in_retailer_vw,'N') = 'Y'
                                             and b.item_no IN ($purchkits)
                                             and a.customer_no='" . $acc[0] . "'
                                             and a.addr_code='" . $acc[1] . "'
                                             and retailers_kit_order_fn(a.customer_no, a.addr_code, b.item_no) = 'Y'"));

                $clear = array('rep'=>FALSE,'accs'=>$this->param);
                Customer::where('customer_no', '=', $acc[0])
                    ->where('addr_code', '=', $acc[1])
                    ->delete();
                break;

            case 'changed': //ONLY CHANGED CUSTOMERS
                $params = $this->changedCustomers();
                if($params) {
                    $acc_criteria = $params["changed"];
                    $cc_data = $this->get_cc_data(FALSE,$acc_criteria,TRUE);
                    $kits_purchased = DB::connection('tyret')->select(
                        DB::raw("select a.customer_no, a.addr_code, b.item_no
                                                 from im_item_master b, ar_cust_addr_code a
                                                 WHERE
                                                 nvl(b.include_in_retailer_vw,'N') = 'Y'
                                                 and b.item_no IN ($purchkits)
                                                 and a.customer_no||'-'||a.addr_code IN ($acc_criteria)
                                                 and retailers_kit_order_fn(a.customer_no, a.addr_code, b.item_no) = 'Y'"));

                    $query = $params["query"];
                    $clear = array('rep'=>FALSE,'accs'=>$acc_criteria);
                    $this->param = $acc_criteria;
                }
                break;
        }

   //     $customerController->clearCustomers($clear);

        if($query !== FALSE){
            $has_kits = array();
            foreach($kits_purchased as $result){
                $has_kits["$result->customer_no-$result->addr_code"][] = $result->item_no;
            }

            $results = DB::connection('tyret')->select(DB::raw($query));
            $i=0;

            foreach($results as $result){
                $i++;
                $customer_deets = array();
                foreach($result as $key=>$val){
                    $customer_deets["$key"] = $val;
                }
                if($this->option == "changed"){
                    Customer::where('customer_no', '=', $result->customer_no)
                        ->where('addr_code', '=', $result->addr_code)
                        ->delete();
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

                $customerController->addCustomer($customer_deets);

            }
            $noun = $this->param ? $this->param : $repstring;
            $time_end = time();
            $job = (new EmailManager(
                array(
                    'to'=>'scoleman@redacted.com',
                    'subject'=>'Customer Sync Complete',
                    'message'=>"Sync for $noun complete. Total Recs: ".$i." <br />".date("H:i:s",$time_end - $time_start)."<br />",
                    'title'=>'Customer Sync'
                )
            ));

            dispatch($job);
        }
    }

    private function changedCustomers()
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

        if(count($results) > 0){
            echo "COUNT: ".count($results);
            $acc_criteria = "";

            foreach($results as $result){
                Customer::where('customer_no','=',$result->customer_no)
                    ->where('addr_code','=',$result->addr_code)
                    ->delete();

                $acc_criteria .= "'".$result->customer_no."-".$result->addr_code."',";

                //$this->syncCustomers($kit_item_nos);
                //    $job = new Customers("customer","$result->customer_no-$result->addr_code");
                //    dispatch($job);
            }
            $acc_criteria = substr($acc_criteria,0,-1);

            $query = "select
                B.customer_no,B.addr_code,B.des1,B.addr1,B.addr2,B.city,B.state_code,B.zip,B.main_phone_no,A.terms_code,B.last_visit_date, A.entity, B.ud_note, B.ud_note7, B.ud_note6,
                A.cust_type_code, A.allow_backorders,A.status as master_status, A.main_contact as bill_main_contact,A.des1 as billing_name, A.addr1 as billing_addr1,A.city as billing_city,A.state_code as billing_state,A.country_code as billing_country,A.zip as billing_zip, A.email_addr as bill_email,
                B.cust_loc_store_code,B.status as status, A.credit_limit,
                A.terr_code, A.sic_code, B.email_addr as email_addr_2, A.multi_loc_qty, B.cust_div_code, A.cust_group_code as mcust_group_code, B.bill_parent_flag, B.main_contact,
                B.cust_group_code as cust_group_code, b.inside_salesperson_no,B.network_group,B.salesperson_no,B.no_pict_req,B.block_order_flag,B.look_book_flag, B.last_ship_date,
                B.ship_method_code,B.latitude,B.longitude, B.price_adj_code, B.country_code, B.disable_combined_genpick_flag AS disable_combine, (SELECT DISTINCT(C.note) FROM ttcus.ty_cust_notes@tycus C
                WHERE C.customer_no=B.customer_no AND C.addr_code = B.addr_code AND status='N' AND rownum=1) AS notes,
                (SELECT F.last_ord_date_boos_fab FROM msr_loc_info@tycus F WHERE F.customer_no=B.customer_no AND F.addr_code=B.addr_code) AS boos_ord_date,
                (SELECT G.last_ord_dol_boos_fab FROM msr_loc_info@tycus G WHERE G.customer_no=B.customer_no AND G.addr_code=B.addr_code) AS boos_last_ord_amt,
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
               AND B.customer_no||'-'||B.addr_code IN ($acc_criteria)";

            return array('changed'=>$acc_criteria,'query'=>$query);
        }
        else{
            return FALSE;
        }

    }

    private function getRepString(){
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

        return $repstring;
    }

    private function getAllCustsQuery($repstring){
        return "select
                B.customer_no,B.addr_code,B.des1,B.addr1,B.addr2,B.city,B.state_code,B.zip,B.main_phone_no,A.terms_code,B.last_visit_date, A.entity, B.ud_note, B.ud_note7, B.ud_note6,
                A.cust_type_code, A.allow_backorders,A.status as master_status, A.main_contact as bill_main_contact,A.des1 as billing_name, A.addr1 as billing_addr1,A.city as billing_city,A.state_code as billing_state,A.country_code as billing_country,A.zip as billing_zip, A.email_addr as bill_email,
                B.cust_loc_store_code,B.status as status, A.credit_limit,
                A.terr_code, A.sic_code, B.email_addr as email_addr_2, A.multi_loc_qty, B.cust_div_code, A.cust_group_code as mcust_group_code, B.bill_parent_flag, B.main_contact,
                B.cust_group_code as cust_group_code, b.inside_salesperson_no,B.network_group,B.salesperson_no,B.no_pict_req,B.block_order_flag,B.look_book_flag, B.last_ship_date,
                B.ship_method_code,B.latitude,B.longitude, B.price_adj_code, B.country_code, B.disable_combined_genpick_flag AS disable_combine, (SELECT DISTINCT(C.note) FROM ttcus.ty_cust_notes@tycus C
                WHERE C.customer_no=B.customer_no AND C.addr_code = B.addr_code AND status='N' AND rownum=1) AS notes, 
                (SELECT F.last_ord_date_boos_fab FROM msr_loc_info@tycus F WHERE F.customer_no=B.customer_no AND F.addr_code=B.addr_code) AS boos_ord_date,
                (SELECT G.last_ord_dol_boos_fab FROM msr_loc_info@tycus G WHERE G.customer_no=B.customer_no AND G.addr_code=B.addr_code) AS boos_last_ord_amt,
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
                        (SELECT F.last_ord_date_boos_fab FROM msr_loc_info@tycus F WHERE F.customer_no=B.customer_no AND F.addr_code=B.addr_code) AS boos_ord_date,
                (SELECT G.last_ord_dol_boos_fab FROM msr_loc_info@tycus G WHERE G.customer_no=B.customer_no AND G.addr_code=B.addr_code) AS boos_last_ord_amt,
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
                       AND B.customer_no=C.customer_no AND B.addr_code = C.addr_code";
    }

    private function getCustSpecQuery($acc){
        return "select
                B.customer_no,B.addr_code,B.des1,B.addr1,B.addr2,B.city,B.state_code,B.zip,B.main_phone_no,A.terms_code,B.last_visit_date, A.entity, B.ud_note, B.ud_note7, B.ud_note6,
                A.cust_type_code, A.allow_backorders,A.status as master_status, A.main_contact as bill_main_contact,A.des1 as billing_name, A.addr1 as billing_addr1,A.city as billing_city,A.state_code as billing_state,A.country_code as billing_country,A.zip as billing_zip, A.email_addr as bill_email,
                B.cust_loc_store_code,B.status as status, A.credit_limit,
                A.terr_code, A.sic_code, B.email_addr as email_addr_2, A.multi_loc_qty, B.cust_div_code, A.cust_group_code as mcust_group_code, B.bill_parent_flag, B.main_contact,
                B.cust_group_code as cust_group_code, b.inside_salesperson_no,B.network_group,B.salesperson_no,B.no_pict_req,B.block_order_flag,B.look_book_flag, B.last_ship_date,
                B.ship_method_code,B.latitude,B.longitude, B.price_adj_code, B.country_code, B.disable_combined_genpick_flag AS disable_combine, (SELECT DISTINCT(C.note) FROM ttcus.ty_cust_notes@tycus C
                WHERE C.customer_no=B.customer_no AND C.addr_code = B.addr_code AND status='N' AND rownum=1) AS notes,
                (SELECT F.last_ord_date_boos_fab FROM msr_loc_info@tycus F WHERE F.customer_no=B.customer_no AND F.addr_code=B.addr_code) AS boos_ord_date,
                (SELECT G.last_ord_dol_boos_fab FROM msr_loc_info@tycus G WHERE G.customer_no=B.customer_no AND G.addr_code=B.addr_code) AS boos_last_ord_amt,
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
                        AND B.customer_no='".$acc[0]."'
                        and B.addr_code='".$acc[1]."'";
    }

    private function getCustRepQuery($repstring){
        return "select
                B.customer_no,B.addr_code,B.des1,B.addr1,B.addr2,B.city,B.state_code,B.zip,B.main_phone_no,A.terms_code,B.last_visit_date, A.entity, B.ud_note, B.ud_note7, B.ud_note6,
                A.cust_type_code, A.allow_backorders,A.status as master_status, A.main_contact as bill_main_contact,A.des1 as billing_name, A.addr1 as billing_addr1,A.city as billing_city,A.state_code as billing_state,A.country_code as billing_country,A.zip as billing_zip, A.email_addr as bill_email,
                B.cust_loc_store_code,B.status as status, A.credit_limit,
                A.terr_code, A.sic_code, B.email_addr as email_addr_2, A.multi_loc_qty, B.cust_div_code, A.cust_group_code as mcust_group_code, B.bill_parent_flag, B.main_contact,
                B.cust_group_code as cust_group_code, b.inside_salesperson_no,B.network_group,B.salesperson_no,B.no_pict_req,B.block_order_flag,B.look_book_flag, B.last_ship_date,
                B.ship_method_code,B.latitude,B.longitude, B.price_adj_code, B.country_code, B.disable_combined_genpick_flag AS disable_combine, (SELECT DISTINCT(C.note) FROM ttcus.ty_cust_notes@tycus C
                WHERE C.customer_no=B.customer_no AND C.addr_code = B.addr_code AND status='N' AND rownum=1) AS notes,
                (SELECT last_ord_date FROM msr_loc_info@tycus D WHERE D.customer_no=B.customer_no AND D.addr_code=B.addr_code) AS boos_ord_date,
                (SELECT last_ord_dol_boos_fab FROM msr_loc_info@tycus E WHERE E.customer_no=B.customer_no AND E.addr_code=B.addr_code) AS boos_last_ord_amt,
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
                        AND B.salesperson_no IN ($repstring)";
    }

    private function syncKits(){
        $kitController = new Kits();
        $kitController->clearKits();
        $results = DB::connection('tyret')->select(DB::raw("SELECT * FROM tr_api_kits WHERE kit_ispublished='Y'"));


        $item_kit_nos = array();

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

    private function get_cc_data($reps=FALSE,$acc=FALSE,$multiLoc=FALSE){

        $credit_card_data = array();

        if($reps){
            $results = DB::connection('tyret')->select(DB::raw("select customer_no||'_'||addr_code as customer,last4,card_exp from credit_data where
    customer_no||'_'||addr_code in (
        select customer_no||'_'||addr_code from ar_cust_addr_code_tydb where
        status in ('A','I')
        AND salesperson_no IN ($reps)
        )
        order by customer_no, addr_code"));
        }
        else if(!$multiLoc){
            $results = DB::connection('tyret')->select(DB::raw("select customer_no||'_'||addr_code as customer,last4,card_exp from credit_data where
      customer_no||'_'||addr_code in (
          select customer_no||'_'||addr_code from ar_cust_addr_code_tydb where
          status in ('A','I')
          AND customer_no='".$acc[0]."'
          and addr_code='".$acc[1]."'
          )
          order by customer_no, addr_code"));
        }
        else{
            $results = DB::connection('tyret')->select(DB::raw("select customer_no||'_'||addr_code as customer,last4,card_exp from credit_data where
      customer_no||'_'||addr_code in (
          select customer_no||'_'||addr_code from ar_cust_addr_code_tydb where
          status in ('A','I')
          AND customer_no||'-'||addr_code IN ($acc)
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
