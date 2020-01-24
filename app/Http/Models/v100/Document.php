<?php
namespace App\Http\Models\v100;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Document extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'documents';
    protected $fillable = array('title','desc','group','groupColor','file');

}
?>
