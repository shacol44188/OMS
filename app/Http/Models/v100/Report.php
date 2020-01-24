<?php
namespace App\Http\Models\v100;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class Report extends Eloquent {

  protected $connection = 'tyret';
  public  $table = 'retailer.pdf_report';


  public $timestamps = false;
  protected $primaryKey = 'report_id';

}
?>
