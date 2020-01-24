<?php
namespace App\Http\Models\v100;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Item extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'items';
    protected $fillable = array('item_no','item_des','item_min_qty_ae','item_max_qty_ae','item_default_qty_ae','item_cost','min_required','include_customers','exclude_customers','commodity_code_real','include_state','exclude_state','include_leadcodes','exclude_leadcodes','include_territory','exclude_territory','include_sic','exclude_sic','include_group_codes','item_notes','exclude_group_codes','lg_img','country_code','pcc','is_other');

}
?>
