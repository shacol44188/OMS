<?php
namespace App\Http\Models\v100\CallReport;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class PictureProps extends Eloquent {

    protected $connection = 'tycus';
    public $table = 'ty_pic_prop';
    protected $fillable = array('pic_id','pic_prop','pic_value');

    public $sequence = null;
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'pic_id';

}
?>
