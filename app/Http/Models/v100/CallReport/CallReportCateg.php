<?php
namespace App\Http\Models\v100\CallReport;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class CallReportCateg extends Eloquent {

    protected $connection = 'tycus';
    public $table = 'ty_rep_loc_categ';
    protected $fillable = array('picture_id','prod_categ','price','pic_sel','pos','create_date');

    public $sequence = null;
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'picture_id';

}
?>
