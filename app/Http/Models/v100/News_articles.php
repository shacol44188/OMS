<?php
namespace App\Http\Models\v100;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class News_articles extends Eloquent {

  protected $connection = 'tyret';
  public  $table = 'tr_api_news';


  public $timestamps = false;
  protected $primaryKey = 'news_id';

}
?>
