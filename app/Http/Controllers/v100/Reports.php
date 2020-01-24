<?php
namespace App\Http\Controllers\v100;

//use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use App\Http\Models\v100\Report;
use App\Http\Models\v100\ReportLog;

use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;

class Reports extends BaseController
{

    public function getReports(){

      $report_return = array();

      /*
      * AVAILABLE REPORTS DEPENDS ON THE USER TYPE
      */
      if($this->user["salesperson_type"] == "RSM" || $this->user["salesperson_type"] == "SFM"){
        if($this->user["userid"] == "JVX"){
          $reports = Report::where('published','=','Y')
                              ->where('access_level','<','3')
                              ->get();
        }
        else{
          $reports = Report::where('published','=','Y')
                              ->where('access_level','<','2')
                              ->get();
        }
      }
      else{
        $reports = Report::where('published','=','Y')
                            ->where('rsm_enabled','=','N')
                            ->get();
      }

      $log = array(
        'action' => "GETTING REPORTS",
        'message' => "FOUND: ".count($reports),
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);

      $i=0;
      foreach($reports as $report){
        $report_return[$i]["title"] = $report->report_title;
        $report_return[$i]["rsm_enabled"] = $report->rsm_enabled;
        $report_return[$i]["desc"] = $report->report_name;
        $report_return[$i]["file"] =  $report->extension == "xlsx" ? $report->report_name."Excel" : $report->report_name;
        $report_return[$i]["extension"] = $report->extension;
        $report_return[$i]["id"] = $report->report_id;
        $report_return[$i]["lookup_id"] = $report->report_id;
        $i++;
      }

      return $this->returnData(array(
        'status' => 0,
        'reports' => $report_return,
        'records' => count($report_return)
      ));
    }

    public function getReport($id){
      $valid_exts = array("doc"=>"msword","pdf"=>"pdf","xls"=>"vnd.ms-excel","xlsx"=>"vnd.ms-excel");

      $report = Report::where('report_id','=',$id)->first();
    //  $file_attribs = explode(".", $report["extension"]);
      $ext = $report["extension"];
      $mime = $valid_exts[$ext];

      $headers=[
        'Content-type' => "application/$mime",
        'Content-Disposition' => "attachment; filename='".$report["file"]."'"
      ];

      //FILENAME IS DIFFERENT IF XLS => NO LONGER REQUIRED TO HAVE DIFFERENT NAME... CHANGE THIS??
      $filename = $ext == "xls" || $ext == "xlsx" ? "_".$report->report_name."Excel.".$report->extension : "_".$report->report_name.".".$report->extension;

      //GET THE REPORT
      $file = storage_path('app/reports/').$this->user["userid"].$filename;
      $report_id = $id;
      if(!file_exists($file)){
        $report_id = 1000+$id;
        $file = storage_path('app/reports/')."default".$filename;
      }

      //RECORD THE VIEW
      $repLog = array(
        'salesperson_no' => $this->user["userid"],
        'report_id' => $report_id,
        'view_date' => date('Y-m-d H:i:s')
      );
      $reportLog = new ReportLog($repLog);
      $reportLog->save();

      $log = array(
        'action' => "VIEW REPORT",
        'message' => "REPORT: ".$report_id,
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);

      return response()->download($file,$report->report_title,$headers);

    }

}
