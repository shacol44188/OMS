<?php

return [

   'default' => 'mongodb',

   'connections' => [
    'mongodb' => [
      'driver'   => env('DB_CONNECTION'),
      'host'     => [env('DB_HOST_ONE'),env('DB_HOST_TWO')],
      'port'     => env('DB_PORT'),
      'database' => env('DB_DATABASE'),
      'username' => env('DB_USERNAME'),
      'password' => env('DB_PASSWORD'),
      'options'  => [
          'database' => 'admin' // sets the authentication database required by mongo 3
      ]
    ],
    'tyret' => [
      'driver'        => env('DB_CONNECTION_SECOND'),
//      'tns'           => env('DB_TNS', ''),
      'host'          => env('DB_HOST_SECOND'),
      'port'          => env('DB_PORT_SECOND'),
      'database'      => env('DB_DATABASE_SECOND'),
      'username'      => env('DB_USERNAME_SECOND'),
      'password'      => env('DB_PASSWORD_SECOND'),
      'charset'       => env('DB_CHARSET', 'AL32UTF8'),
//      'prefix'        => env('DB_PREFIX', ''),
//      'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
    ],
    'tydb' => [
            'driver'        => env('DB_CONNECTION_THIRD'),
      //      'tns'           => env('DB_TNS', ''),
            'host'          => env('DB_HOST_THIRD'),
            'port'          => env('DB_PORT_THIRD'),
            'database'      => env('DB_DATABASE_THIRD'),
            'username'      => env('DB_USERNAME_THIRD'),
            'password'      => env('DB_PASSWORD_THIRD'),
      'charset'       => env('DB_CHARSET', 'AL32UTF8'),
//      'prefix'        => env('DB_PREFIX', ''),
//      'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
    ],
    'tycus' => [
            'driver'        => env('DB_CONNECTION_FOURTH'),
      //      'tns'           => env('DB_TNS', ''),
            'host'          => env('DB_HOST_FOURTH'),
            'port'          => env('DB_PORT_FOURTH'),
            'database'      => env('DB_DATABASE_FOURTH'),
            'username'      => env('DB_USERNAME_FOURTH'),
            'password'      => env('DB_PASSWORD_FOURTH'),
      'charset'       => env('DB_CHARSET', 'AL32UTF8'),
    //      'prefix'        => env('DB_PREFIX', ''),
    //      'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
    ],
    'tydm' => [
            'driver'        => env('DB_CONNECTION_FIFTH'),
      //      'tns'           => env('DB_TNS', ''),
            'host'          => env('DB_HOST_FIFTH'),
            'port'          => env('DB_PORT_FIFTH'),
            'database'      => env('DB_DATABASE_FIFTH'),
            'username'      => env('DB_USERNAME_FIFTH'),
            'password'      => env('DB_PASSWORD_FIFTH'),
      'charset'       => env('DB_CHARSET', 'AL32UTF8'),
  //      'prefix'        => env('DB_PREFIX', ''),
  //      'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
    ],
   ]
];

?>
