<?php
namespace App\Http\Models\v100\CallReport;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class SellThrough extends Eloquent {

    protected $connection = 'tycus';
    public $table = 'ty_rep_loc_inv';
    protected $fillable = array('visit_id','floor_date','categ_code','recd','sold','reord_ind');

    public $sequence = null;
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'visit_id';

}
?>
