<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => '/api'], function () use ($router){
  $router->group(['prefix' => '/v100','namespace' => 'v100', 'middleware'=>['auth','verifyts']], function () use ($router){
    //INFO
    $router->get('/getInfo/','Info@getInfo');
    $router->post('/ignoreSeasonal/','Info@ignoreSeasonal');
    //REPS
    $router->post('/login','Auth@login');
    $router->get('/validateToken/','Auth@validateToken');
    //ITEMS
    $router->get('/getItems/', 'Items@getItems');
    //CUSTOMERS
    $router->get('/getCustomer/{customer_loc}', 'Customers@getCustomer');
    $router->get('/getCustomers/{skip}', 'Customers@getCustomers');
    $router->post('/closeAccount/', 'Customers@closeAccount');
    //ORDERS
    $router->get('/getOrdersLive/{skip}', 'Orders@getOrdersLive');
    $router->get('/getOrderLineItemsLive/{skip}', 'Orders@getOrderLineItemsLive');
    $router->post('/placeOrder/','Orders@placeOrder');
    $router->post('/emailpending/','Orders@emailOrder');
      $router->get('/ccInfo/','Orders@getCCInfo');
    //DISCOUNTS
    $router->get('/getDiscounts/', 'Discounts@getDiscounts');
    //DOCUMENTS
    $router->get('/getDocuments/','Documents@getDocuments');
    $router->get('/getDocument/{id}','Documents@getDocument');
    //REPORTS
    $router->get('/getReports/','Reports@getReports');
    $router->get('/getReport/{id}','Reports@getReport');
    //KITS
    $router->get('/getKits/', 'Kits@getKits');
    //NEWS
    $router->get('/getNews/', 'News@getNews');
    //CALL REPORT
    $router->post('/submitCallReport/','CallReports@submitCallReport');

    //ADMIN CONTROLS
    $router->group(['prefix'=>'/admin','middleware'=>['verifyadmin']], function () use ($router){
      $router->get('/getLogs',array('as'=>'/getLogs','uses'=>'Info@emailLogs'));
      $router->get('/syncCustomers[/{rep}]','Customers@syncCustomers');
      $router->get('/syncCustomer/{customer}','Customers@syncCustomer');
      $router->get('/syncChanged','Customers@syncChanged');
      $router->get('/syncKits/','Kits@syncKits');
      $router->get('/syncDiscounts/','Discounts@syncDiscounts');
    });
/*
    //AUTOMATED SYSTEM
    $router->group(['prefix'=>'/automated'], function () use ($router){

      $router->get('/', function () use ($router) {
          return $router->app->version();
      });

      //ITEMS
      $router->post('/addItem/', 'Items@addItem');
      $router->post('/addBulkItem/', 'Items@addBulkItem');
      $router->post('/addLine/', 'Items@addLine');
      $router->post('/addBulkLine/', 'Items@addBulkLine');
      $router->post('/addBulkCategory/', 'Items@addBulkCategory');

      //CUSTOMERS
      $router->post('/addCustomer/', 'Customers@addCustomer');
      $router->post('/addBulkCustomer/', 'Customers@addBulkCustomer');

      //ORDERS
      $router->post('/addOrder/', 'Orders@addOrder');
      $router->post('/addOrderLineItem/', 'Orders@addOrderLineItem');
      $router->post('/addFullOrder/', 'Orders@addFullOrder');

      //DISCOUNTS
      $router->post('/addDiscount/', 'Discounts@addDiscount');

      //KITS
      $router->post('/addKit/', 'Kits@addKit');

      //NEWS
      $router->post('/addNews/', 'News@addNews');
    });
    */
  });

});
