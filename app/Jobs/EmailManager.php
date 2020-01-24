<?php

namespace App\Jobs;

use App\Jobs\MasterJob;

class EmailManager extends MasterJob{

  private $headers = array();
  private $body;
  private $params = array(
    "to" => "",
    "from" => "noreply@redacted.com",
    "replyto" => "helpdesk@redacted.com",
    "subject" => "",
    "message" => ""
  );

  private $email_type = 0;
  private $object = array();

  public function __construct($params,$object=NULL,$email_type=0){

    foreach($params as $key => $value){
      $this->params[$key] = $value;
    }

    $this->headers = array(
      'MIME-Version'=> "1.0",
      'Content-type'=> "text/html; charset=iso-8859-1",
      'From' => $this->params["from"],
      'Reply-To' => $this->params["replyto"],
      'X-Mailer' => 'PHP/' . phpversion()
    );

    $this->headers = array();
    $this->headers[] = 'MIME-Version: 1.0';
    $this->headers[] = 'Content-type: text/html; charset=iso-8859-1';
    $this->headers[] = 'From: '.$this->params["from"];
    $this->headers[] = 'Reply-To: '.$this->params["replyto"];
    $this->headers[] = 'X-Mailer: PHP/'.phpversion();

    $this->object = $object;
    $this->email_type = $email_type;


  }

  public function handle(){
    switch($this->email_type){
      case 0: $this->customEmail();
      break;
      case 1: $this->orderConfirmationTable();
      break;
      case 2: $this->callReportConfirmation();
      break;
      case 3: $this->emailLogs();
      break;
    }

    $this->delete();
  }

  private function callReportConfirmation(){
    $callReport = $this->object;
  }

  private function orderConfirmationTable(){
    $ord_no = "N/A";
    $order = $this->object["order"];
    $customer = $this->object["customer"];

    $order_line_items = "";

    $message = "<table width=\"800px\" class=\"item_list\">";

    if($this->object["style"] == 2){
      $order_line_items .= "
      <tr class=\"heading\">
        <td>NAME: </td>
        <td>UPC: </td>
        <td>QTY: </td>
        <td>PRICE: </td>
        <td>TOTAL: </td>
      </tr>
      ";
      foreach($this->object["items"] as $line_item){
        $order_line_items .=
        "<tr>
            <td>". $line_item["item"][0]->item_des . "</td>
            <td>".$line_item["sku"]."</td>
            <td>".$line_item["li"]["qty_ord"]."</td>
            <td>$".$line_item["item"][0]->item_cost."</td>
            <td>$".$line_item["li"]["qty_ord"] * $line_item["item"][0]->item_cost."</td>
        </tr>";
      }
    }
    else{
      $order_line_items .= "
      <tr class=\"heading\">
        <td width=\"80px\">IMAGE</td>
        <td>NAME: </td>
        <td>UPC: </td>
        <td>QTY: </td>
        <td>PRICE: </td>
        <td>TOTAL: </td>
      </tr>
      ";
      foreach($this->object["items"] as $line_item){
        $lg_img = $line_item["item"][0]->lg_img;
        $order_line_items .= "
        <tr>
          <td width=\"80px\" style=\"text-align: center;\"><img width=\"80\" src=\"$lg_img\" /><br />".$line_item["item"][0]->item_no."</td>
          <td>". $line_item["item"][0]->item_des . "</td>
          <td>".$line_item["sku"]."</td>
          <td>".$line_item["li"]["qty_ord"]."</td>
          <td>$".$line_item["item"][0]->item_cost."</td>
          <td>$".$line_item["li"]["qty_ord"] * $line_item["item"][0]->item_cost."</td>
        </tr>";
      }
    }

    $message .= $order_line_items;
    $message .= "</table>";

    if(isset($order["ord_no"])){ //IF WE HAVE AN ORDER NUMBER, THIS IS ORDER CONFIRMATION => OTHERWISE IT IS PENDING ORDER EMAIL
      $ord_no = $order["ord_no"];
      $this->params["message"] = '
      <table>
        <tr>
          <td class=\"heading\">
          	    	<h2>BILL TO:</h2>
          </td>
        </tr>
        <tr>
          <td>
            <table>
              <tr>
                <td>'.$customer["billing_name"].'</td>
              </tr>
              <tr>
                <td>'.$customer["billing_addr1"].'</td>
              </tr>
              <tr>
                <td>'.$customer["billing_city"].' '.$customer["billing_state"].','.$customer["billing_zip"].'</td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td>
            <table>
              <tr>
            	  <td class=\"heading\"><h2>SHIP TO:</h2></td>
              </tr>
              <tr>
                <td>'.$customer["des1"].'</td>
              </tr>
              <tr>
                <td>'.$customer["addr1"].'</td>
              </tr>
              <tr>
                <td>'.$customer["city"].' '.$customer["state_code"].','.$customer["zip"].'</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      <hr />';
    }
    $this->params["message"] .= '
    <table>
      <tr>
        <td class=\"heading\">
      	    	<h2>ORDER SUMMARY:</h2>
	      </td>
      </tr>
              	<tr><td><label>Customer Number</label>: '.$customer["customer_no"].'</td></tr>
              	<tr><td><label>Order Number</label>: '.$ord_no.'</td></tr>
              	<tr><td><label>Commodity</label>: APP</td></tr>
              	<tr><td><label>PO Number</label>: '.$order["po_num"].'</td></tr>
               	<tr><td><label>Order Terms</label>: '.$order["terms_code"].'</td></tr>
               	<tr><td><label>Order Date</label>: '.date('d-M-y H:i:s').'</td></tr>
                <tr><td><label>Future Ship</label>: '.$order["future_ship_date"].'</td></tr>
                <tr><td><label>Lead Code</label>: '.$order["lead_source_code"].'</td></tr>
              	<tr><td><label>Total</label>: $'.$order["order_total"].'</td></tr>
    </table>
    <hr/>
    '.$message;

    $this->generateTableBody($this->object["style"] == 1 ? TRUE : FALSE);

    mail($this->params["to"],$this->params["subject"],$this->params["message"],implode("\r\n", $this->headers));
  }

  private function orderConfirmation(){
    $ord_no = "N/A";
    $order = $this->object["order"];
    $customer = $this->object["customer"];

    $order_line_items = "";

    if($this->object["style"] == 2){
      foreach($this->object["items"] as $line_item){
        $order_line_items .= "<li>Item: ".$line_item["item"][0]->item_no."   UPC: ".$line_item["sku"]."   Qty: ".$line_item["li"]["qty_ord"]."    Price: $".$line_item["item"][0]->item_cost."    Name: " . $line_item["item"][0]->item_des . "</li>";
      }
    }
    else{
      foreach($this->object["items"] as $line_item){
        $lg_img = $line_item["item"][0]->lg_img;
        $order_line_items .= "<li><img width=\"80px\" src=\"$lg_img\" />   Item: ".$line_item["item"][0]->item_no."   UPC: ".$line_item["sku"]."   Qty: ".$line_item["li"]["qty_ord"]."    Price: $".$line_item["item"][0]->item_cost."    Name: " . $line_item["item"][0]->item_des . "</li>";
      }
    }

    if(isset($order["ord_no"])){ //IF WE HAVE AN ORDER NUMBER, THIS IS ORDER CONFIRMATION => OTHERWISE IT IS PENDING ORDER EMAIL
      $ord_no = $order["ord_no"];
      $this->params["message"] = '
      <div class="billing_details">
        <div class="heading">
        	    	BILL TO:
        </div>
        <ol>
          <li>'.$customer["billing_name"].'</li>
          <li>'.$customer["billing_addr1"].'</li>
          <li>'.$customer["billing_city"].' '.$customer["billing_state"].','.$customer["billing_zip"].'</li>
        </ol>
        <div class="heading">
        	    	SHIP TO:
        </div>
        <ol>
        <li>'.$customer["des1"].'</li>
        <li>'.$customer["addr1"].'</li>
        <li>'.$customer["city"].' '.$customer["state_code"].','.$customer["zip"].'</li>
        </ol>
      </div>
      <hr />';
    }
    $this->params["message"] .= '
    <div class="order_info">
            <div class="heading">
      	    	ORDER SUMMARY:
	      </div>
      	  <ol>
              	<li><label>Customer Number</label>: '.$customer["customer_no"].'</li>
              	<li><label>Order Number</label>: '.$ord_no.'</li>
              	<li><label>Commodity</label>: APP</li>
              	<li><label>PO Number</label>: '.$order["po_num"].'</li>
               	<li><label>Order Terms</label>: '.$order["terms_code"].'</li>
               	<li><label>Order Date</label>: '.date('d-M-y H:i:s').'</li>
                <li><label>Future Ship</label>: '.$order["future_ship_date"].'</li>
                <li><label>Lead Code</label>: '.$order["lead_source_code"].'</li>
              	<li><label>Total</label>: $'.$order["order_total"].'</li>
      	 </ol>
    </div>
            <div class="heading">
      	    	ORDER LINE ITEMS:
	          </div>
            <ol>
              '.$order_line_items.'
            </ol>
      </div>
  	</div>
    ';

    $this->generateBody();

    mail($this->params["to"],$this->params["subject"],$this->params["message"],implode("\r\n", $this->headers));
  }

  private function customEmail(){
    $this->generateTableBody();
    mail($this->params["to"],$this->params["subject"],$this->params["message"],implode("\r\n", $this->headers));
  }

  private function emailLogs(){
    $logs = $this->object;

    $message = "";

    foreach($logs as $log){

      $message .= "CREATED AT: ".$log->created_at."<br />";
      $message .= "ACTION: ".$log->action."<br />";
      $message .= "TYPE: ".$log->msg_type."<br />";
      $message .= "MESSAGE: ".$log->message."<br />";
      $message .= "SEVERITY: ".$log->severity."<br />";
      $message .= "TYPE: ".$log->msg_type."<br />";
      $message .= "API: ".$log->api_version."<br />";
      $message .= "RECEIVED: ".$log->recd."<br />";
      $message .= "USER: ".$log->userid."<br />";
      $message .= "SERVER: ".$log->server."<br /><br />";
    }

    $this->params["message"] .= $message;
    $this->generateTableBody(TRUE);

    if(count($logs) > 0) {
        mail($this->params["to"],$this->params["subject"],$this->params["message"],implode("\r\n", $this->headers));
    }

  }

  private function generateBody(){
    $this->params["message"] = "
    <!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"
      \"http://www.w3.org/TR/html4/loose.dtd\">
      <html>
      <head>
    <style type=\"text/css\">
      body{
      	background-color: gray;
      }

      div.body{
      	width: 90%;
      margin-left: auto;
      margin-right: auto;
      background-color: darkgray;
      border: solid 2px white;
      }
      header{
      	text-align: center;
      	margin-bottom: 2em;
        width: 100%;
      }
      header div{
      	display: inline-block;
      	width: 70%;
      }
      header div.icon{
      	width: 10%;
      }
      li{
      	list-style: none;
      }
    </style>
    </head>
    <body>

    <div>
      <header>
        <div class=\"icon\">
          <img src=\"https://tycdn.azureedge.net/static/dist/client/img/24cfb4f.png\" width=\"100\" />
        </div>
        <div>
          <h1>".$this->params["title"]."</h1>
        </div>
        <div class=\"icon\">
          <img src=\"https://tycdn.azureedge.net/static/dist/client/img/24cfb4f.png\" width=\"100\" />
        </div>
      </header>
      <div class=\"body\">
        ".$this->params["message"]."
      </div>
    </div>
    </body>
    </html>
    ";
  }

  private function generateTableBody($images = FALSE){
    $message = "
    <html>
      <head>
        <style type=\"text/css\">
          html{
            width: 100%;
          }
          table{
            background-color: white;
          }
          label,td.heading,tr.heading td{
            font-weight: bold;
          }
          td{
            border: solid 1px transparent;
          }
          td.content, td.content table{
            background-color: white;
          }
          td.content{
            border: solid 3px white;
          }
          td.tbl_hdr_logo{
            width: 25%;
            text-align: center;
          }
          td.tbl_hdr{
            width: 45%;
          }
        </style>
      </head>
      <body>
        <table>
          <tr>
            <td class=\"tbl_hdr_logo\" height=\"100\" width=\"100\">
              ";
              if($images){
                $message .= "<img src=\"https://tycdn.azureedge.net/static/dist/client/img/24cfb4f.png\" height=\"100\" /> ";
              }
            $message .= "
            </td>
            <td class=\"tbl_hdr\" style=\"text-align: center;\"><h2>".$this->params["title"]."</h2></td>
            <td class=\"tbl_hdr_logo\" height=\"100\" width=\"100\">
            ";
            if($images){
              $message .= "<img src=\"https://tycdn.azureedge.net/static/dist/client/img/24cfb4f.png\" height=\"100\" /> ";
            }
          $message .= "
            </td>
          </tr>
          <tr>
            <td></td>
            <td class=\"content\">
              ".$this->params["message"]."
            </td>
            <td></td>
          </tr>
        </table>
      </body>
    </html>
    ";

    $this->params["message"] = $message;
  }


}

?>
