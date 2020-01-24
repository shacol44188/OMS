<?php
$servers = array(
  'TYOMS1' => array(
    'host' => 'tyoms1.redacted.com',
    'services' => array(
      'API' => '443',
      'MONGO' => '27017'
    )
  ),
  'TYOMS2' => array(
    'host' => 'tyoms2.redacted.com',
    'services' => array(
      'API' => '443',
      'MONGO' => '27017'
    )
  ),
  'TYOMS3' => array(
    'host' => 'tyoms3.redacted.com',
    'services' => array(
      'MONGO' => '27017'
    )
  ),
);

$errors = array();
$waitTimeoutInSeconds = 1;

foreach($servers as $server){
  $host = $server["host"];
  foreach($server["services"] as $service=>$port){
      try {
        $fp = @fsockopen($host,$port,$errCode,$errStr,$waitTimeoutInSeconds);
        if (!$fp) {
          throw new ErrorException("SERVER: $host\nSERVICE: $service\n\n");
        }
      }
      catch (\ErrorException $e) {
        $errors[] = $e->getMessage();
      }
    }
  }

if(count($errors) > 0){
  $message = implode("\n\n", $errors);
  mail('scoleman@redacted.com','URGENT - CONNECTIVITY ISSUE WITH TYOMS', $message);
}
?>
