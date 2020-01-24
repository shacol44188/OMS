<?php
namespace App\Http\Models\v100;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Kit extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'kits';
    protected $fillable = array('name','item_no','items','is_exception','exception_items');

}
?>
