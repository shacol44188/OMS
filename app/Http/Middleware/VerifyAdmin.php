<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class VerifyAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    private $session_data;

    /*
    * VERIFY USER IS ADMIN
    */

    public function handle($request, Closure $next)
    {

      $this->session_data = app('Illuminate\Contracts\Auth\Guard')->user();

      if($this->session_data["usertype"] == 99){
        return $next($request);
      }
      else{
        return array(
          'status' => 1,
          'message' => "NOT ADMIN",
          'user_info' => $this->session_data
        );
      }
    }
}
