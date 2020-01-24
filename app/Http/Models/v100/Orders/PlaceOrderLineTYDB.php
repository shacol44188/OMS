<?php
namespace App\Http\Models\v100\Orders;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class PlaceOrderLineTYDB extends Eloquent {

    protected $connection = 'tydb';
    public $table = "wo_line";
    protected $fillable = array('ord_no','item_no','qty_ord');

    public $timestamps = false;
    protected $primaryKey = 'ord_no';

}
?>
