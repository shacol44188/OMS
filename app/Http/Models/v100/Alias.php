<?php
namespace App\Http\Models\v100;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class Alias extends Eloquent {

    protected $connection = 'tyret';
    public $table = 'tr_api_authorization';

    public $sequence = null;
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'auth_alias';

}
?>
