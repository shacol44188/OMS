<?php
namespace App\Http\Models\v100\Orders;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class PlaceOrderHeader extends Eloquent {

    protected $connection = 'tyret';
    public $table = "wo_hdr";
    protected $fillable = array('ord_no', 'customer_no', 'cust_addr_code', 'salesperson_no',
                                'wo_agent', 'lead_source_code', 'order_total', 'primary_commodity_code',
                                'terms_code', 'c_no', 'credit_card_exp', 'cc_name',
                                'cc_addr1', 'cc_addr2', 'cc_city', 'cc_state', 'cc_zip',
                                'po_num', 'complete_flag', 'hold_reason', 'future_ship_date',
                                'program_flag', 'ud_field_1', 'no_freight_flag',
                                'app_guid', 'guarantee_flag', 'date_created', 'entry_date');

  //  public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;
    protected $primaryKey = 'ord_no';

}
?>
