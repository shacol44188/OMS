<?php
namespace App\Http\Models\v100;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Discount extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'discounts';
    protected $fillable = array('cart','cartExceptions');

}
?>
