<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class VerifyToken
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
    * TRADESHOW GATEWAY
    * THREE POSSIBILITIES
    * 1. USER IS ATTEMPTING TRADESHOW ACCESS AND IS REGISTERED
    * 2. USER IS NOT ATTEMPTING TRADESHOW ACCESS
    * 3. USER IS ATTEMPTING TRADESHOW ACCESS AND IS NOT REGISTERED
    */

    public function handle($request, Closure $next)
    {

      $this->session_data = app('Illuminate\Contracts\Auth\Guard')->user();

      $ts = $request->input('ts') == "Y" ? TRUE : FALSE;
      if(!$ts || ($ts && $this->session_data["tradeshow"] == "Y")){ //USER IS NOT ATTEMPTING TRADESHOW - OR - USER IS ATTEMPTING AND IS REGISTERED => CONTINUE
        return $next($request);
      }
      else if($ts){ // USER IS ATTEMPTING TRADESHOW ACCESS AND IS NOT REGISTERED => BOOT 'EM OUT!
        return array(
          'status' => 1,
          'message' => "NOT REGISTERED FOR TRADESHOW",
          'user_info' => $this->session_data
        );
      }
    }
}
