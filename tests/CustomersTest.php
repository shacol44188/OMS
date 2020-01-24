<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class CustomersTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */

     //EXPECT REGULAR CUSTOMERS FOR AUTHORIZED REP
     public function testGetCustomersAuth()
     {
         $this->get('/api/v100/getCustomers/0?api_token='.$this->api_token)
               ->seeJson([
                       'status' => 0,
                    ]);
     }

     //EXPECT TRADESHOW CUSTOMERS
     public function testGetCustomersAuthTS()
     {
       $this->get('/api/v100/getCustomers/0?api_token='.$this->api_token."&ts=Y")
             ->seeJson([
                     'status' => 0,
                  ]);
     }

     //EXPECT UNAUTHORIZED
     public function testGetCustomersUnAuth()
     {
         $response = $this->call('GET','/api/v100/getCustomers/0?api_token=invalid');
         $this->assertEquals(401, $response->status());

     }

     //EXPECT UNAUTHORIZED
     public function testGetCustomersUnAuthTS()
     {
         $response = $this->call('GET','/api/v100/getCustomers/0?api_token=invalid&ts=Y');
         $this->assertEquals(401, $response->status());
     }

     //EXPECT AUTHORIZED USER UNREGISTERED TS
     public function testGetCustomersAuthUnregTS()
     {
       $this->get('/api/v100/getCustomers/0?api_token='.$this->api_token_tsfail.'&ts=Y')
             ->seeJson([
                     'status' => 1,
                  ]);
     }


}

?>
