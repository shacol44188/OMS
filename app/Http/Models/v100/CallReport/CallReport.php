<?php
namespace App\Http\Models\v100\CallReport;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class CallReport extends Eloquent {

    protected $connection = 'tycus';
    public $table = 'ty_rep_loc';
    protected $binaries = ['db_image','db_image2','db_image3','db_image4','db_image5','db_image6'];
    protected $fillable = array('customer_no','latitude','longitude','notes','qty','status','picture_prop','addr_code','new_addr','repcode','replogin','fu_date','pct_ty','pic_categ','email_status','last_sug_order','mbb_price','tt_price','boo_price','display','competition','adj_customer_no','adj_addr_code','visit_id','ib_size','ib_notes','ib_ratio','cdate','session_id','timezone_date','cm_status','image_path','image_path2','image_path3','image_path4','image_path5','image_path6','db_image','db_image2','db_image3','db_image4','db_image5','suggested_order');

    public $sequence = null;
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'visit_id';

}
?>
