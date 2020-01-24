<?php
namespace App\Http\Models\v100;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class Session extends Eloquent {

    protected $connection = 'tyret';
    public $table = 'tr_api_auth';
    protected $fillable = array('userid','userid_real','token','token_expires','json');

    public $sequence = null;
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'userid';

}
?>
