<?php
namespace App\Http\Models\v100;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class Customer extends Eloquent {

    protected $connection = 'tyret';
    protected $collection = 'tr_api_customers';
    protected $fillable = array('customer_no','addr_code','des1','addr1','addr2','city','state_code','zip','main_phone_no','terms_code','last_visit_date','entity','ud_note','ud_note7','ud_note6','cust_type_code','allow_backorders','master_status','bill_main_contact','bill_email','cust_loc_store_code','status','credit_limit','terr_code','sic_code','email_addr_2','multi_loc_qty','cust_div_code','mcust_group_code','bill_parent_flag','main_contact','cust_group_code','inside_salesperson_no','network_group','salesperson_no','no_pict_req','block_order_flag','look_book_flag','last_ship_date','ship_method_code','latitude','longitude','price_adj_code','country_code','disable_combine','notes','po_required_flag','last_ord_date','last_ord_year','prev_pct_ty','display_type1','display_type2','display_type3','display_type4','ud_note2','val_ind','eas_ind','hwn_ind','chr_ind','addtnl_info','credit_cards','pccs','info','has_kits',
        'bill_main_contact','billing_name','billing_addr1','billing_city','billing_state','billing_country','billing_zip','boos_ord_date','boos_last_ord_amt');

}
?>
