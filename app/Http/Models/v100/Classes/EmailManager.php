<?php

namespace App\Http\Models\v100\Classes;

class EmailManager{

  private $headers = array();
  private $params = array(
    "to" => "",
    "from" => "noreply@redacted.com",
    "replyto" => "helpdesk@redacted.com",
    "subject" => "",
    "message" => ""
  );

  public function __construct($params){

    $this->headers = array(
      'From' => $this->params["from"],
      'Reply-To' => $this->params["replyto"],
      'X-Mailer' => 'PHP/' . phpversion()
    );

    foreach($params as $key => $value){
      $this->params[$key] = $value;
    }
  }

  public function orderConfirmation($order){

  }

  public function customEmail(){
    mail($this->params["to"],$this->params["subject"],$this->params["message"],$this->headers);
  }



}

?>
