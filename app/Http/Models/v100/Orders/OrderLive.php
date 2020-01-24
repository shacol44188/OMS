<?php
namespace App\Http\Models\v100\Orders;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class OrderLive extends Eloquent {

    protected $connection = 'tyret';
    public  $table = 'ioe_last_ord_info';


    public $timestamps = false;
    protected $primaryKey = 'ord_no';
    //protected $fillable = array('terr_code','salesperson_no','customer_no','addr_code','ord_no','cust_po_no','future_ship_date','cancel_date','lead_source_code','order_date','line_no','item_no','qty_ord','qty_pick','tot_qty_shipped','status','ship_date','last_ord_no','mod_unit_price','ord_hold_ind','load_date','tot_ord_amt');

}
?>
