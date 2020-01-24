<?php
namespace App\Http\Models\v100;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class ReportLog extends Eloquent {

  protected $connection = 'tyret';
  public  $table = 'pdf_report_audit';
  protected $fillable = array('salesperson_no','report_id','view_date');


  public $timestamps = false;
  protected $primaryKey = 'report_id';

}
?>
