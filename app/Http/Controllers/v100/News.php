<?php

namespace App\Http\Controllers\v100;

//use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use App\Http\Models\v100\News_articles;

class News extends BaseController
{
    //
    public function getNews(){
      $news = News_articles::all();

      $log = array(
        'action' => "GETTING NEWS",
        'message' => "FOUND: ".count($news),
        'severity' => count($news) > 0 ? env('LOG_SEVERITY_ZERO') : env('LOG_SEVERITY_LOW')
      );

      $this->log($log);

      return array(
        'status'=>0,
        'news'=>$news
      );
    }

    //AUTOMATED SETUP

    public function addNews(){
      $news = new News_articles(Input::all());
      $news->save();

      $log = array(
        'action' => "AUTOMATED: ADDING NEWS",
        'message' => "AUTOMATED: ADDING NEWS",
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);
    }
}
