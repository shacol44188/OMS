<?php

namespace App\Jobs;

use App\Jobs\MasterJob;
use App\Http\Controllers\v100\Discounts as DiscountController;
use Illuminate\Support\Facades\DB;

class Discounts extends MasterJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $this->init();

        $discountController = new DiscountController();
    //    $discountController->clearDiscounts();

        $time_start = time();
        $discounts = array();

        $discounts ["cart"] ["GuaranteeProgram"] = "";

        $discounts ["cart"] ["FreightDiscount"]["US"] = 1000;
        $discounts ["cart"] ["FreightDiscount"]["CA"] = 1500;

        $discounts ["cart"] ["FreeFreight"]["US"] = 1500;
        $discounts ["cart"] ["FreeFreight"]["CA"] = 2000;

        $discounts ["cart"] ["Hardcode"]["US"] = 3000;
        $discounts ["cart"] ["Hardcode"]["CA"] = 3500;

        $discounts ["cart"] ["ProgramDiscount"]["US"] = 1000;
        $discounts ["cart"] ["ProgramDiscount"]["CA"] = 1500;

        $discounts ["cart"] ["minAmt"]["US"] = 200;
        $discounts ["cart"] ["minAmt"]["CA"] = 250;

        $discounts ["cart"] ["resetDate"] = "2016-01-04";

        $discounts ["cart"] ["itemExceptions"][0]["item"] = "42308";
        $discounts ["cart"] ["itemExceptions"][0]["amount"] = 63;

        $discounts ["cartExceptions"][0]["customer_no"] = "112999";
        $discounts ["cartExceptions"][0]["amount"] = 100;

        $discounts ["cartExceptions"][1]["customer_no"] = "119890";
        $discounts ["cartExceptions"][1]["amount"] = 100;

        $discounts ["cartExceptions"][2]["customer_no"] = "120646";
        $discounts ["cartExceptions"][2]["amount"] = 100;

        $discounts ["cartExceptions"][3]["customer_no"] = "120436";
        $discounts ["cartExceptions"][3]["amount"] = 100;

        $discounts ["cartExceptions"][4]["customer_no"] = "120960";
        $discounts ["cartExceptions"][4]["amount"] = 100;

        $discounts ["cartExceptions"][5]["customer_no"] = "120902";
        $discounts ["cartExceptions"][5]["amount"] = 100;

        $discounts ["cartExceptions"][6]["customer_no"] = "118277";
        $discounts ["cartExceptions"][6]["amount"] = 100;

        $discounts ["cartExceptions"][7]["customer_no"] = "121182";
        $discounts ["cartExceptions"][7]["amount"] = 100;

        $discounts ["cartExceptions"][8]["customer_no"] = "121264";
        $discounts ["cartExceptions"][8]["amount"] = 100;

        $discounts ["cartExceptions"][9]["customer_no"] = "121181";
        $discounts ["cartExceptions"][9]["amount"] = 100;

        $discounts ["cartExceptions"][10]["customer_no"] = "120325";
        $discounts ["cartExceptions"][10]["amount"] = 100;

        $discounts ["cartExceptions"][11]["customer_no"] = "121362";
        $discounts ["cartExceptions"][11]["amount"] = 100;

        $discounts ["cartExceptions"][12]["customer_no"] = "123003";
        $discounts ["cartExceptions"][12]["amount"] = 100;

        $discounts ["cartExceptions"][13]["customer_no"] = "122499";
        $discounts ["cartExceptions"][13]["amount"] = 100;

        $discounts ["cartExceptions"][14]["customer_no"] = "122358";
        $discounts ["cartExceptions"][14]["amount"] = 100;

        $discounts ["cartExceptions"][15]["customer_no"] = "115230";
        $discounts ["cartExceptions"][15]["amount"] = 100;

        $discounts ["cartExceptions"][16]["customer_no"] = "123346";
        $discounts ["cartExceptions"][16]["amount"] = 100;

        $discounts ["cartExceptions"][17]["customer_no"] = "123364";
        $discounts ["cartExceptions"][17]["amount"] = 100;

        $discounts ["cartExceptions"][18]["customer_no"] = "123365";
        $discounts ["cartExceptions"][18]["amount"] = 100;

        $discounts ["cartExceptions"][19]["customer_no"] = "123367";
        $discounts ["cartExceptions"][19]["amount"] = 100;

        $discounts ["cartExceptions"][20]["customer_no"] = "123368";
        $discounts ["cartExceptions"][20]["amount"] = 100;

        $discounts ["cartExceptions"][21]["customer_no"] = "123369";
        $discounts ["cartExceptions"][21]["amount"] = 100;

        $discounts ["cartExceptions"][22]["customer_no"] = "123449";
        $discounts ["cartExceptions"][22]["amount"] = 100;

        $discounts ["cartExceptions"][23]["customer_no"] = "123441";
        $discounts ["cartExceptions"][23]["amount"] = 100;

        $discounts ["cartExceptions"][24]["customer_no"] = "123442";
        $discounts ["cartExceptions"][24]["amount"] = 100;

        $discounts ["cartExceptions"][25]["customer_no"] = "115097";
        $discounts ["cartExceptions"][25]["amount"] = 100;

        //ADD EXCEPTION FOR ALL CVS ACCOUNTS *AND* FOR ALL ACCOUNTS WITH SIC_CODE OF RITEAD

        $results = DB::connection('tyret')->select(DB::raw("SELECT customer_no FROM ar_customer_master@tydb WHERE (des1 LIKE '%CVS%') OR sic_code IN ('RITEAD','7ELEVN')"));

        $i = 26;

        foreach($results as $result)
        {
          $discounts['cartExceptions'][$i]["customer_no"] = $result->customer_no;
          $discounts['cartExceptions'][$i]["amount"] = 100;
          $i++;
        }
        $discountController->addDiscount($discounts);

        $time_end = time();

        $job = (new EmailManager(
          array(
            'to'=>'scoleman@redacted.com',
            'subject'=>'Discounts Sync Complete',
            'message'=>"Total Recs: ".count($discounts["cartExceptions"])." <br />".date("H:i:s",$time_end - $time_start),
            'title'=>'Discount Sync'
          )
        ));

        dispatch($job);
    }
}
