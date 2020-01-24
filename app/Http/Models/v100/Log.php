<?php
namespace App\Http\Models\v100;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Log extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'log';
    protected $fillable = array('action','userid','message','recd','msg_type','severity','api_version','server');

}
?>
