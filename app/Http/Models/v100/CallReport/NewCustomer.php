<?php
namespace App\Http\Models\v100\CallReport;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class NewCustomer extends Eloquent {

    protected $connection = 'tydb';
    public $table = "ct_names_stage";
    public $keys = array('entity','contact_ref','des1','addr1','city','state_code','zip','country_code','leadsrc_type_code','lead_source_code','contact_name','contact_phone','contact_email','visit_id','region_code','customer_no','salesperson_no');
    protected $fillable = array('entity','contact_ref','des1','addr1','city','state_code','zip','country_code','leadsrc_type_code','lead_source_code','contact_name','contact_phone','contact_email','visit_id','region_code','customer_no','salesperson_no');

    //public $sequence = "";
    public $incrementing = false;

    // Database uses trigger to update timestamp
    public $timestamps = false;

    protected $primaryKey = 'customer_no';

}
?>
