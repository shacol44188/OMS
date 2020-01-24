<?php
namespace App\Http\Controllers\v100;

//use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\v100\MasterController as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use App\Http\Models\v100\Document;

class Documents extends BaseController
{

    private $documents = array();

    public function __construct(){
        $i=0;

        $documents[$i]["title"] = "PSR Extension";
        $documents[$i]["desc"] = "PSR Extension List";
        $documents[$i]["group"] = "Internal/HR Documents";
        $documents[$i]["groupColor"] = "16|247|224";
        $documents[$i]["file"] = "PSR_Ext_List (Updated 2-23-18).xls";
        $documents[$i]["id"] = $i;
        $i++;

        $this->documents = $documents;
    }

    public function getDocuments(){

      $log = array(
        'action' => "GETTING DOCUMENTS",
        'message' => "FOUND: ".count($this->documents),
        'severity' => env('LOG_SEVERITY_ZERO')
      );

      $this->log($log);

      return $this->returnData(array(
              'status' => 0,
              'documents' => $this->documents
      ));
    }

    public function getDocument($id){
      $valid_exts = array("doc"=>"msword","pdf"=>"pdf","xls"=>"vnd.ms-excel");

      $document = $this->documents[$id];
      $file_attribs = explode(".", $document["file"]);
      $ext = $file_attribs[1];
      $mime = $valid_exts[$ext];

      $headers=[
        'Content-type' => "application/$mime",
        'Content-Disposition' => "attachment; filename='".$document["file"]."'"
      ];

      return response()->download(storage_path('app/documents/').$document["file"],$document["title"],$headers);
    }

}
