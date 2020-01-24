<?php
namespace App\Http\Models\v100\Orders;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Order extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'orders';
    protected $fillable = array('terr_code','salesperson_no','customer_no','addr_code','ord_no','cust_po_no','future_ship_date','cancel_date','lead_source_code','order_date','line_no','item_no','qty_ord','qty_pick','tot_qty_shipped','status','ship_date','last_ord_no','mod_unit_price','ord_hold_ind','load_date','tot_ord_amt','primary_commodity_code');

}
?>
