<?php

namespace App\Http\Controllers\v100;

//use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Http\Models\v100\Item;
use App\Http\Models\v100\Orders\Order;
use App\Http\Models\v100\Orders\OrderLive;
use App\Http\Models\v100\Orders\OrderLineItem;
use App\Http\Models\v100\Orders\FullOrderLineItem;
use App\Http\Models\v100\Orders\PlaceOrderHeader;
use App\Http\Models\v100\Orders\PlaceOrderLine;
use App\Http\Models\v100\Orders\PlaceOrderHeaderTYDB;
use App\Http\Models\v100\Orders\PlaceOrderLineTYDB;
use App\Http\Models\v100\Customer;
use App\Jobs\EmailManager;

class Orders extends BaseController
{
    private $limit = 10000;

    public function getOrdersLive($skip){
        $orders = OrderLive::select('ord_no','customer_no','order_date','addr_code','last_ord_no','future_ship_date','salesperson_no','ord_hold_ind','tot_ord_amt')
            ->distinct()
            ->where('salesperson_no','=', $this->user["userid"])
            ->whereIn('last_ord_no', array(1, 2, 3, 4))
            ->take(intval($this->limit))
            ->skip(intval($skip*$this->limit))
            ->get();

        $log = array(
            'action' => "GETTING ORDERS",
            'message' => "PASS: $skip. FOUND: ".count($orders),
            'severity' => count($orders) > 0 ? env('LOG_SEVERITY_ZERO') : env('LOG_SEVERITY_LOW')
        );

        $this->log($log);

        return array(
            'status'=>0,
            'orders'=>$orders,
            'noOfRecs'=>Order::where('salesperson_no','=',$this->user["userid"])->count(),
            'limit'=>$this->limit
        );
    }

    public function getOrderLineItemsLive($skip){
        $orders = OrderLive::select('item_no','ord_no','customer_no','order_date','addr_code','last_ord_no','future_ship_date','salesperson_no','ord_hold_ind','tot_ord_amt','item_des1','ship_date','status','qty_pick','qty_ord','tot_qty_shipped','line_no')
            ->where('salesperson_no','=', $this->user["userid"])
            ->whereIn('last_ord_no', array(1, 2, 3, 4))
            ->take(intval($this->limit))
            ->skip(intval($skip*$this->limit))
            ->get();

        $log = array(
            'action' => "GETTING ORDER LINE ITEMS",
            'message' => "PASS: $skip. FOUND: ".count($orders),
            'severity' => count($orders) > 0 ? env('LOG_SEVERITY_ZERO') : env('LOG_SEVERITY_LOW')
        );

        $this->log($log);

        return array(
            'status'=>0,
            'orderLineItems'=>$orders,
            'noOfRecs'=>OrderLive::where('salesperson_no','=',$this->user["userid"])->count(),
            'limit'=>$this->limit
        );
    }

    public function emailOrder(){
        $order = Input::get("json");
        if(!is_array($order)){
            $order = json_decode($order,true);
        }

        $log = array(
            'action' => "PREPARING TO EMAIL ORDER",
            'severity' => env('LOG_SEVERITY_ZERO')
        );
        $this->log($log);

        //FETCH CUSTOMER FOR EMAIL
        $customer = Customer::where('customer_no','=',$order["order"]["customer_no"])->where('addr_code','=',$order["order"]["cust_addr_code"])->first();

        //GET ITEMS
        $items = $this->prepLineItems($order["order"]["line_items"],$customer);

        //SET UP EMAIL PARAMATERS
        $email_data = array(
            'order' => $order["order"],
            'items' => $items,
            'customer' => $customer,
            'style' =>  $order["order"]["email_to"] == "rep" ? 1 : $order["order"]["email_style"]
        );

        $job = new EmailManager(
            array(
                'title'=>"ORDER REVIEW",
                'to'=> $order["order"]["email_to"] == "rep" ? $this->user["email"] : $order["order"]["email_to"],
                'subject'=>'Order Review for account '.$customer->customer_no.'-'.$customer->addr_code
            ),
            $email_data,
            1);
        $this->dispatchNow($job);

        return $this->returnData(array(
            'status' => 0
        ));
    }

    public function placeOrder(){

        $order = Input::get("json");
        if(!is_array($order)){
            $order = json_decode($order,true);
        }

        $log = array(
            'action' => "PREPARING TO PLACE ORDER",
            'severity' => env('LOG_SEVERITY_ZERO')
        );
        $this->log($log);

        //CHECK IF ORDER IS DUPLICATE
        if(!$this->checkIfDupe($order["order"]["app_guid"])){
            //GET ORDER NUMBER
            $results = DB::connection('tyret')->select(DB::raw("select order_num_wo_hdr.nextval as ord_no from dual"));
            $ord_no = $results[0]->ord_no;
            $order["order"]["ord_no"] = $ord_no;

            //ADDITIONAL ORDER PROPERTIES
            $order["order"]["wo_agent"] = $this->user["userid"];
            $order["order"]["hold_reason"] = "APP ORDER";
            $order["order"]["primary_commodity_code"] = isset($order["order"]["pcc"]) ? $order["order"]["pcc"] : "MIXED";
            $order["order"]["date_created"] = strtoupper ( date ( "d-M-y" ) );

            //FORMAT FUTURE SHIP DATE
            if(strlen($order["order"]["future_ship_date"]) > 8){
                $future_ship_date = trim(preg_replace('~[^\d\-:.]~', ' ', $order["order"]["future_ship_date"])); //CLEAN OUT ANY UNWANTED CHARACTERS
                $fsd = date_create_from_format('Y-m-d H:i:s u',$future_ship_date); //TELL PHP WHAT FORMAT THE DATE IS IN
                $fsd = strtoupper(date_format($fsd,'Y-m-d'));
                $order["order"]["future_ship_date"] = $fsd;
            }
            else{
                $order["order"]["future_ship_date"] = NULL;
            }

            //CHECK TERMS => IF CC, GET CREDIT CARD INFO
            if($order["order"]["terms_code"] == "CC"){
                $cc_info = $this->getCCInfo($order["order"]["customer_no"],$order["order"]["cust_addr_code"],$order["order"]["cred_no"]);
                foreach($cc_info[$order["order"]["customer_no"]."-".$order["order"]["cust_addr_code"]] as $key=>$val){
                    $order["order"][$key] = $val;
                }
            }

            //CREATE ORDER
            $neworder = new PlaceOrderHeader($order["order"]);
            $neworder->save();

            $newordertydb = new PlaceOrderHeaderTYDB($order["order"]);
            $newordertydb->save();

            //FETCH CUSTOMER FOR EMAIL
            $customer = Customer::where('customer_no','=',$order["order"]["customer_no"])->where('addr_code','=',$order["order"]["cust_addr_code"])->first();

            //PREPARE LINE ITEMS
            $items = $this->prepLineItems($order["order"]["line_items"],$customer,$ord_no);

            //SET UP EMAIL PARAMATERS
            $email_data = array(
                'order' => $order["order"],
                'items' => $items,
                'customer' => $customer,
                'style' => 2
            );

            $job = new EmailManager(
                array(
                    'title'=>"ORDER CONFIRMATION",
                    //'to'=>"lliang@redacted.com",
                    'to'=>$this->user["email"],
                    'subject'=>'Order Confirmation #'.$ord_no.' for account '.$customer->customer_no.'-'.$customer->addr_code
                ),
                $email_data,
                1);
            $this->dispatchNow($job);

            $msgs = "#$ord_no PLACED";
            $status = env('LOG_SEVERITY_ZERO');
        }
        else{
            $ord_no = "N/A";
            $msgs = "ORDER #$ord_no HAS ALREADY BEEN SUBMITTED";
            $status = env('LOG_SEVERITY_LOW');
        }

        $log = array(
            'action' => "PLACING ORDER",
            'message' => $msgs."\n\n".json_encode($order),
            'msg_type' => 1,
            'severity' => $status
        );
        $this->log($log);

        return $this->returnData(array(
            'status' => $status,
            'submission' => $order,
            'order_number' => $ord_no,
            'msgs' => $msgs
        ));

    }
    private function getCCInfo($customer_no, $addr_code, $last4){
        $results = DB::connection('tyret')->select(DB::raw("select card_no as c_no, card_exp as credit_card_exp,
                                                          cardholder as cc_name, card_addr1 as cc_addr1, card_addr2 as cc_addr2, card_city as cc_city,
                                                          card_state as cc_state, card_zip as cc_zip, last4	from credit_data
                                                          where last4 = '$last4' and customer_no = '$customer_no'
                                                          and addr_code = '$addr_code'"));


        return array(
            $customer_no-$addr_code => $results[0]
        );
    }
    private function prepLineItems($line_items,$customer,$ord_no=false){
        $items = array();
        $i=0;
        $processed = array();
        foreach($line_items as $line_item){
            if(!in_array($line_item["item_no"], $processed)){
                $line_item["ord_no"] = $ord_no !== FALSE ? $ord_no : null;
                if($ord_no !== FALSE){
                    $l_item = new PlaceOrderLine($line_item);
                    $l_item->save();
                    $l_itemtydb = new PlaceOrderLineTYDB($line_item);
                    $l_itemtydb->save();
                }

                //FETCH ITEM DETAILS FOR EMAIL
                $items[$i]["li"] = $line_item;
                $country_code = $customer["country_code"];
                $items[$i]["item"] = DB::connection('tyret')->select(DB::raw("select A.item_no, A.item_des, A.item_min_qty_ae, A.item_max_qty_ae, A.item_default_qty_ae,
                    A.item_cost, A.min_required, A.include_customers, A.exclude_customers, A.commodity_code_real, A.include_state, A.exclude_state,
                    A.include_leadcodes, A.exclude_leadcodes, A.include_territory, A.exclude_territory, A.include_sic, A.exclude_sic, A.include_group_codes, A.item_notes,
                      A.exclude_group_codes,I.image_hash||'_lg'||I.image_ext as lg_image_name,'https://tycdn.azureedge.net/static/images/'||I.image_hash||'_lg.'||I.image_ext as lg_img, A.country_code , A.pcc
                      from item_master A left outer join im_item_master I on A.item_no=I.item_no
                      where A.pcc='MIXED'
                      AND A.country_code='".$country_code."'
                      AND A.item_no='".$line_item["item_no"]."'"));
                $items[$i]["sku"] = $this->makeSku($line_item["item_no"]);
                $processed[] = $line_item["item_no"];
                $i++;
            }
        }
        return $items;
    }
    private function makeSku($sku){
        $tsku = intval($sku);
        if ($tsku>0) {
            $tempsku = "008421".str_pad($sku,5,"0",STR_PAD_LEFT);
            $tx = 1;
            $temp = 0;
            while($tx<=11) {
                $temp = $temp + intval(substr($tempsku,$tx-1,1));
                $tx = $tx+2;
            }
            $firstval = $temp*3;
            $tx = 2;
            $temp =0;
            while($tx<=10) {
                $temp = $temp + intval(substr($tempsku,$tx-1,1));
                $tx = $tx+2;
            }
            $secondval = $temp;
            $thirdval = substr(intval($firstval+$secondval),-1);
            $checksum = (10-$thirdval);
            if ($checksum=="10") { $checksum="0"; }
            $sku = $tempsku . $checksum;

        } else {
            $sku="";
        }
        return $sku;
    }

    private function checkIfDupe($orderId){
        $order = PlaceOrderHeader::where('app_guid','=',$orderId)->get();

        return count($order) > 0 ? TRUE : FALSE;
    }


    //AUTOMATED SETUP

    public function addOrder(){
        $order = new Order(Input::all());
        $order->save();

        $log = array(
            'action' => "AUTOMATED: ADDING ORDER",
            'message' => "#".$order["ord_no"],
            'severity' => env('LOG_SEVERITY_ZERO')
        );

        $this->log($log);
    }

    public function addOrderLineItem(){
        $orderLineItem = new OrderLineItem(Input::all());
        $orderLineItem->save();

        $log = array(
            'action' => 'AUTOMATED: ADDING LINE ITEM',
            'message' => "FOR ORDER #".$orderLineItem["ord_no"],
            'severity' => env('LOG_SEVERITY_ZERO')
        );

        $this->log($log);
    }

    public function addFullOrder(){
        $orders = Input::all();

        //  error_log(print_r($orders,TRUE));

        $processed = array();

        foreach($orders as $order){
            $ord = $order;

            if(isset($ord["ord_no"])){
                //error_log(print_r($ord["ord_no"],TRUE));
                if(!in_array($ord["ord_no"], $processed)){
                    //  error_log("ORD ".$ord["ord_no"]." NOT FOUND IN ".print_r($processed,TRUE));
                    $prev_order = Order::where('ord_no','=',$ord["ord_no"])->get();

                    if(!isset($prev_order[0]->ord_no)){
                        $new_order = new Order($ord);
                        $new_order->save();
                        $processed[] = $ord["ord_no"];
                    }
                }
                $new_order_li = new OrderLineItem($ord);
                $new_order_li->save();
            }
            /*
    */
        }

    }

}
