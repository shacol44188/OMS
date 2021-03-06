<?php
namespace App\Http\Models\v100\CallReport;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class NewCustomerLocal extends Eloquent {

    protected $connection = 'tyret';
    public $table = "ct_names_stage_local";
    public $keys = array('entity','contact_ref','des1','addr1','city','state_code','zip','country_code','leadsrc_type_code','lead_source_code','contact_name','contact_phone','contact_email','visit_id','region_code','customer_no','salesperson_no','tc_image');
    protected $binaries = ['tc_image'];
    protected $fillable = array('entity','contact_ref','des1','addr1','city','state_code','zip','country_code','leadsrc_type_code','lead_source_code','contact_name','contact_phone','contact_email','visit_id','region_code','customer_no','salesperson_no','tc_image');

    //public $sequence = "";
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'visit_id';

}
?>
