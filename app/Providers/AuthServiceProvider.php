<?php

namespace App\Providers;

//use App\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use DateTime;

use App\Http\Models\v100\User;
use App\Http\Models\v100\Rep;
use App\Http\Models\v100\Alias;
use App\Http\Models\v100\Session;
use App\Http\Models\v100\Audit;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {

            if ($request->input('api_token')) {
              $session = Session::where('token', '=', $request->input('api_token'))->where('token_expires','>',strtoupper ( date ( "d-M-y") ))->first();
              if(isset($session["userid"])){
                //GET THE USER
                $user = User::where('userid','=',$session["userid"])->first();
              }
            }
            else if($request->input('username') && $request->input('password')){
              //CREATE A NEW TOKEN AND RETURN
              $username = strtoupper($request->input('username'));
              $unsecpw = $request->input('password');
              $password = md5($request->input('password'));

              $build = $request->input('build');

              //GET USER FROM REAL USERS
              $user = User::where([
                                   'userid' => $username,
                                   'password' => $password,
                            ])->first();

              //IF NOT FOUND, CHECK AGAINST ALIAS
              if(!isset($user["userid"])){
                $alias = Alias::where([
                                     'auth_alias' => $username,
                                     'auth_pw' => $unsecpw,
                              ])->first();
                error_log("whut");
                if(isset($alias["auth_map"])){
                  $user = User::where('userid','=',$alias["auth_map"])->first();
                }
              }

              if(isset($user["userid"])){
                $session = $this->generateNewSession($user["userid"]);
                Audit::where('salesperson_no','=',$user["userid"])->delete();
                $audit = new Audit(array('salesperson_no'=>$user["userid"],'api_build'=>$build.'.100'));
                $audit->save();
              //  return $user_session;
              }
            }
            if(isset($user)){
              $salesperson = Rep::where('salesperson_no','=',$session["userid"])->first();
              $session_data = array(
                'userid' => $user["userid"],
                'usertype' => $user["usertype"],
                'salesperson_type' => $user["salesperson_type"],
                'email' => $user["email"],
                'terr_code' => $user["terr_code"],
                'region' => $salesperson["region"],
                'country_code' => $user["country_code"],
                'api_token' => $session["token"],
                'tradeshow' => "N",
                'is_ts' => FALSE
              );
              //CHECK IF TRADESHOW
              $ts = $request->input('ts') == "Y" ? TRUE : FALSE;
              if($ts){
                $ts = $this->isValidTradeShow($session["userid"]);
                if($ts){
                  $session_data["is_ts"] = TRUE;
                  $session_data["tradeshow"] = $ts["tradeshow"];
                  $session_data["lead_source_codes"] = $ts["lead_source_codes"];
                }
              }
              return $session_data;
            }

        });
    }
    private function isValidTradeShow($userid){
      $results = DB::connection('tyret')->select(DB::raw("select * from tr_trade_show_setup"));

      $reps = explode('|',$results[0]->reps_at_shows);
      $lscs = explode('|',$results[0]->valid_lead_sources);

      if(in_array($userid, $reps)){
        return array(
          'tradeshow' => "Y",
          'lead_source_codes' => $lscs
        );
      }
      return false;
    }
    private function generateNewSession($userid){

      $session = Session::where('userid','=',$userid);
      $session->delete();

      $token = md5($userid.microtime());
      //$expires = date('r',time() + 60*60*24*3);

      $date = new DateTime('now');
      date_add($date, date_interval_create_from_date_string('+3 days'));
      $expires = date_format($date, 'Y-m-d');

      error_log($expires);

      $newSession = new Session();
      $newSession["userid"] = $userid;
      $newSession["token"] = $token;
      $newSession["token_expires"] = $expires;

      $newSession->save();

      return $newSession;
    }
}
