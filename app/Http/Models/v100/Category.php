<?php
namespace App\Http\Models\v100;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Category extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'categories';
    protected $fillable = array('hdr_id','sort_order','hdr_format','des1','c_code','headers','sizes');

}
?>
