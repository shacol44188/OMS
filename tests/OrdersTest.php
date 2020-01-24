<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class OrdersTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */

  //  private $api_token = "75560a96bbf0a07416ee22b380fe1b49";

    //EXPECTS ORDERS
    public function testGetOrders()
    {
        $this->get('/api/v100/getOrdersLive/1?api_token='.$this->api_token)
              ->seeJson([
                      'status' => 0,
                   ]);

    }

    //EXPECTS ORDER LINE ITEMS
    public function testGetOrderLineItems()
    {
      $this->get('/api/v100/getOrderLineItemsLive/1?api_token='.$this->api_token)
            ->seeJson([
                    'status' => 0,
                 ]);
    }

    //EXPECTS UNAUTHORIZED
    public function testGetOrdersUnAuth()
    {
      $response = $this->call('GET','/api/v100/getOrdersLive/0?api_token=invalid');
      $this->assertEquals(401, $response->status());
    }

    public function testPlaceOrderCCUnder1000(){
      $json = '
      {
        "json" : {
		      "order" : {
  	        "app_guid" : "BBA9D2DE-9B64-447A-99DA-D57A4BF72F81",
  	        "cred_no" : "8100",
  	        "cust_addr_code" : "00",
  	        "customer_no" : "13894",
  	        "future_ship_date" : "2019-10-11 15:10:57 +0000",
  	        "guarantee_flag" : "N",
  	        "hard_code_flag" : "N",
  	        "lead_source_code" : "NA",
            "line_items" : [
  	        	{"item_no" : "36841","qty_ord" : "6"},
  	          {"item_no" : "37244","qty_ord" : "2"},
  	          {"item_no" : "37264","qty_ord" : "4"},
  	          {"item_no" : "36870","qty_ord" : "6"},
  	          {"item_no" : "36890","qty_ord" : "2"},
  	          {"item_no" : "36667","qty_ord" : "6"},
  	          {"item_no" : "36281","qty_ord" : "6"},
  	          {"item_no" : "36186","qty_ord" : "6"},
  	          {"item_no" : "36849","qty_ord" : "6"},
  	          {"item_no" : "37149","qty_ord" : "4"}
  	        ],
  	        "no_freight_flag" : "N",
  	        "order_total" : "202",
  	        "pcc" : "MIXED",
  	        "po_num" : "MIS TEST",
  	        "program_flag" : "N",
  	        "salesperson_no" : "EPE",
  	        "terms_code" : "CC",
  	        "ud_field_1" : "(null)"
  	      }
  	   }
     }
     ';

      $headers = [ 'CONTENT_TYPE' => 'application/json' ];
      $response = $this->call(
          'POST',
          '/api/v100/placeOrder?api_token='.$this->api_token,
          [],
          [],
          [],
          $headers,
          $json
      );

      $this->assertEquals(200, $response->status());
    }

    public function testPlaceOrderTermsUnder1000(){
      $json = '
      {
        "json" : {
		      "order" : {
  	        "app_guid" : "BBA9D2DE-9B64-447A-99DA-D57A4BF72F80",
  	        "cred_no" : "(null)",
  	        "cust_addr_code" : "00",
  	        "customer_no" : "13894",
  	        "future_ship_date" : "2019-10-11 15:10:57 +0000",
  	        "guarantee_flag" : "N",
  	        "hard_code_flag" : "N",
  	        "lead_source_code" : "NA",
  	        "line_items" : [
  	        	{"item_no" : "36841","qty_ord" : "6"},
  	          {"item_no" : "37244","qty_ord" : "2"},
  	          {"item_no" : "37264","qty_ord" : "4"},
  	          {"item_no" : "36870","qty_ord" : "6"},
  	          {"item_no" : "36890","qty_ord" : "2"},
  	          {"item_no" : "36667","qty_ord" : "6"},
  	          {"item_no" : "36281","qty_ord" : "6"},
  	          {"item_no" : "36186","qty_ord" : "6"},
  	          {"item_no" : "36849","qty_ord" : "6"},
  	          {"item_no" : "37149","qty_ord" : "4"}
  	        ],
  	        "no_freight_flag" : "N",
  	        "order_total" : "202",
  	        "pcc" : "MIXED",
  	        "po_num" : "MIS TEST",
  	        "program_flag" : "N",
  	        "salesperson_no" : "EPE",
  	        "terms_code" : "NET30",
  	        "ud_field_1" : "(null)"
  	      }
  	   }
     }
     ';

      $headers = [ 'CONTENT_TYPE' => 'application/json' ];
      $response = $this->call(
          'POST',
          '/api/v100/placeOrder?api_token='.$this->api_token,
          [],
          [],
          [],
          $headers,
          $json
      );

      $this->assertEquals(200, $response->status());
    }

    //EXPECTS TO SEND EMAIL OF PENDING ORDER
    public function testEmailPendingOrder(){
      $json = '
      {	"json" : {
      		"order" : {
      	        "app_guid" : "BBA9D2DE-9B64-447A-99DA-D57A4BF72F74",
      	        "c_no" : "(null)",
      	        "cust_addr_code" : "00",
      	        "customer_no" : "13894",
      	        "future_ship_date" : "2019-10-11 15:10:57 +0000",
      	        "guarantee_flag" : "N",
      	        "hard_code_flag" : "N",
      	        "lead_source_code" : "NA",
      	        "line_items" : [
      	        	{
      	                "item_no" : "36841",
      	                "qty_ord" : "6"
      	            },
      	            {
      	                "item_no" : "37244",
      	                "qty_ord" : "2"
      	            },
      	            {
      	                "item_no" : "37264",
      	                "qty_ord" : "4"
      	            },
      	            {
      	                "item_no" : "36870",
      	                "qty_ord" : "6"
      	            },
      	            {
      	                "item_no" : "36890",
      	                "qty_ord" : "2"
      	            },
      	                        {
      	                "item_no" : "36667",
      	                "qty_ord" : "6"
      	            },
      	            {
      	                "item_no" : "36281",
      	                "qty_ord" : "6"
      	            },
      	            {
      	                "item_no" : "36186",
      	                "qty_ord" : "6"
      	            },
      	            {
      	                "item_no" : "36849",
      	                "qty_ord" : "6"
      	            },
      	            {
      	                "item_no" : "37149",
      	                "qty_ord" : "4"
      	            }
      	        ],
      	        "no_freight_flag" : "N",
      	        "order_total" : "202",
      	        "pcc" : "MIXED",
      	        "po_num" : "MIS TEST",
      	        "program_flag" : "N",
      	        "salesperson_no" : "EPE",
      	        "terms_code" : "CC",
      	        "ud_field_1" : "(null)",
      	        "email_to" : "scoleman@redacted.com",
      	        "email_style" : 1
      	    }
      	}
      }
      ';

      $headers = [ 'CONTENT_TYPE' => 'application/json' ];
      $response = $this->call(
          'POST',
          '/api/v100/emailpending?api_token='.$this->api_token,
          [],
          [],
          [],
          $headers,
          $json
      );

      $this->assertEquals(200, $response->status());



//      dd($this->response->getContent());

    }
}
