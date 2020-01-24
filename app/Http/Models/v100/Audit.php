<?php
namespace App\Http\Models\v100;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class Audit extends Eloquent {

    protected $connection = 'tyret';
    public $table = 'tr_api_audit';
    protected $fillable = array('salesperson_no','api_build');

    public $sequence = null;
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'salesperson_no';

}
?>
