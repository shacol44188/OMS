<?php
namespace App\Http\Models\v100;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class CloseAccount extends Eloquent {

    protected $connection = 'tyret';
    public $table = 'ioe_addr_code_notify';

    public $sequence = null;
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'customer_no';

}
?>
