<?php

namespace App\Http\Controllers\v100;

//use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Jobs\SyncManager;
use App\Http\Models\v100\Item;
use App\Http\Models\v100\Category;
use App\Http\Models\v100\Line;

class Items extends BaseController
{

    public function getItems(){

        DB::connection('tyret')->statement(DB::raw("alter session set nls_date_format = 'MM/DD/YYYY HH24:MI'"));

        if($this->user["tradeshow"] == "Y"){
          //$items = Item::all();
          $items=DB::connection('tyret')->select(DB::raw("select A.item_no, A.item_des, A.item_min_qty_ae, A.item_max_qty_ae, A.item_default_qty_ae,
                      A.item_cost, A.min_required, A.include_customers, A.exclude_customers, A.commodity_code_real, A.include_state, A.exclude_state,
                      A.include_leadcodes, A.exclude_leadcodes, A.include_territory, A.exclude_territory, A.include_sic, A.exclude_sic, A.include_group_codes, A.item_notes,
                        A.exclude_group_codes,I.image_hash||'_lg'||I.image_ext as lg_image_name,'https://tycdn.azureedge.net/static/images/'||I.image_hash||'_lg.'||I.image_ext as lg_img, A.country_code , A.pcc
                        from pcc B, item_master A left outer join im_item_master I on A.item_no=I.item_no
                        where A.pcc IN (SELECT pcc FROM pcc WHERE allow_order='y' AND pcc_livedate <= SYSDATE AND pcc_deaddate >= SYSDATE)
                        and A.country_code=B.country_code and A.commodity_code_real=B.pcc and
                         (A.item_livedate = '01/01/1900 00:00' or A.item_livedate <= SYSDATE) and
                        (A.item_deaddate = '01/01/1900 00:00' or A.item_deaddate >= SYSDATE) and A.allow_order = 'y'
                      order by B.sortorder, A.commodity_code_real, A.sort_order, A.item_cost, A.item_no, 10"));
        }
        else{
        //  $items = Item::where('country_code','=',$this->user["country_code"])->get();
          $items=DB::connection('tyret')->select(DB::raw("select A.item_no, A.item_des, A.item_min_qty_ae, A.item_max_qty_ae, A.item_default_qty_ae,
                    A.item_cost, A.min_required, A.include_customers, A.exclude_customers, A.commodity_code_real, A.include_state, A.exclude_state,
                    A.include_leadcodes, A.exclude_leadcodes, A.include_territory, A.exclude_territory, A.include_sic, A.exclude_sic, A.include_group_codes, A.item_notes,
                      A.exclude_group_codes,I.image_hash||'_lg'||I.image_ext as lg_image_name,'https://tycdn.azureedge.net/static/images/'||I.image_hash||'_lg.'||I.image_ext as lg_img, A.country_code , A.pcc
                      from pcc B, item_master A left outer join im_item_master I on A.item_no=I.item_no
                      where A.pcc IN (SELECT pcc FROM pcc WHERE allow_order='y' AND pcc_livedate <= SYSDATE AND pcc_deaddate >= SYSDATE)
                      and A.country_code=B.country_code and A.commodity_code_real=B.pcc and
                       (A.item_livedate = '01/01/1900 00:00' or A.item_livedate <= SYSDATE) and
                      (A.item_deaddate = '01/01/1900 00:00' or A.item_deaddate >= SYSDATE) and A.allow_order = 'y'
                      AND A.country_code='".$this->user["country_code"]."'
                    order by B.sortorder, A.commodity_code_real, A.sort_order, A.item_cost, A.item_no, 10"));
        }

        foreach($items as $item){
          $item->size = $this->getSize($item->item_des);
        }

        $ofrm_hdrs = DB::connection('tyret')->select(DB::raw("select A.hdr_id,sort_order,hdr_format,des1,B.c_code,B.headers,sizes from tr_ofrm_header A, tr_ofrm_header_prices B
                where
                A.hdr_id = B.hdr_id
                AND start_date <= SYSDATE and end_date >= SYSDATE order by sort_order"));

        $ofrm_lines = DB::connection('tyret')->select(DB::raw("select line_id,hdr_id,sort_order,des1,items from tr_ofrm_line where start_date <= SYSDATE and end_date >= SYSDATE order by hdr_id,sort_order"));

        $log = array(
          'action' => "GETTING ITEMS",
          'message' => "FOUND: ".count($items),
          'severity' => count($items) > 0 ? env('LOG_SEVERITY_ZERO') : env('LOG_SEVERITY_LOW')
        );

        $this->log($log);


        return $this->returnData(array(
          'status' => 0,
          'items' => $items,
          'oFrmHdr' => $ofrm_hdrs,
          'oFrmLine' => $ofrm_lines,
          'itemCount' => count($items)
        ));
    }

    private function getSize($item_des) {
    		$size = "UNK";

    		$sizes = array("DISPLAYER","TEEN","CLIP","SML","REG","MED","LRG","LARGE","KIT","PURSE","BACK PACK","WRISTLET");
    		foreach($sizes as $sizeable){
    			if(stripos($item_des, $sizeable) !== FALSE){
    				$size = $sizeable;
    				switch($sizeable){
    					case "DISPLAYER":
    						$size = "DISP";
    					break;
    					case "LARGE":
    						$size = "LRG";
    					break;
    					case "PURSE":
    						$size = "PRS";
    					break;
    					case "BACK PACK":
    						$size = "BKPK";
    					break;
    					case "WRISTLET":
    						$size = "WRST";
    					break;
    				}
    			}
    		}

    		return $size;
    	}

    //ADMIN CONTROLS
    public function syncItems(){
      $this->clearItems();

      $job = (new SyncManager("items"));

      $this->dispatchNow($job);
    }

    public function clearItems(){
      if($this->user["usertype"] == 99){
        Item::truncate();
      }
    }

    //ADMIN QUEUES/JOBS
    public function addItem($item_deets){
      $item = new Item($item_deets);
      $item->save();

      $log = array(
        'action' => "AUTOMATED: ADDING ITEM",
        'message' => "ADDING ITEM",
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);
    }
}
