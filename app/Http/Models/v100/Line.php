<?php
namespace App\Http\Models\v100;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Line extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'lines';
    protected $fillable = array('line_id','hdr_id','sort_order','des1','items');

}
?>
