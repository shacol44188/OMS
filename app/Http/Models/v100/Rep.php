<?php
namespace App\Http\Models\v100;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class Rep extends Eloquent {

    protected $connection = 'tyret';
    public $table = 'ar_salesperson_code_tydb';

    public $sequence = null;
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'salesperson_no';

}
?>
