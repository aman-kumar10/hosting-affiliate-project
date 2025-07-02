<?php

namespace WHMCS\Module\Addon\Affiliate;

use DateTime;
use Exception;
use WHMCS\Module\Addon\Affiliate\Api;

use WHMCS\Database\Capsule;

require_once __DIR__ . '/../../../../init.php';


class Helper
{

    public function getAffiliatesDataTable($start, $length, $search = '')
    {
        $query = Capsule::table('tblaffiliates')
            ->select('id', 'date', 'clientid', 'balance', 'withdrawn');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('date', 'like', "%$search%")
                    ->orWhere('clientid', 'like', "%$search%")
                    ->orWhere('balance', 'like', "%$search%")
                    ->orWhere('withdrawn', 'like', "%$search%");
            });
        }

        $affiliates = $query->skip($start)->take($length)->get();


        $data = [];
        foreach ($affiliates as $affiliate) {

            $client = Capsule::table("tblclients")->where("id", $affiliate->clientid)->first();
            $currency = getCurrency($client->id);
            if($client) {
                $data[] = [
                    'check_box' => '<input type="checkbox" name="selectedaffiliate[]" value="' . $affiliate->id . '" class="checkall">',
                    'name' => '<a href="affiliates.php?action=edit&id='. $affiliate->id . ' ">' . $client->firstname. ' ' . $client->lastname .'</a>',
                    'email' => '<a href="clientssummary.php?userid='. $client->id . ' ">' . $client->email .'</a>',
                    'balance' => $currency['prefix'] . $affiliate->balance . " " . $currency['suffix'],
                    'withdrawn' => $currency['prefix'] . $affiliate->withdrawn . " " . $currency['suffix'],
                    'date' => $affiliate->date,
                ];
            }
            
        }

        return $data;
    }
    public function getAffiliatesCount($search = '')
    {
        $query = Capsule::table('tblaffiliates')
            ->select('id', 'date', 'clientid', 'balance', 'withdrawn');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('date', 'like', "%$search%")
                    ->orWhere('clientid', 'like', "%$search%")
                    ->orWhere('balance', 'like', "%$search%")
                    ->orWhere('withdrawn', 'like', "%$search%");
            });
        }

        return $query->count();
    }

    public function affiliate_program(){
        /**
         * Get the product data form database for connect with prodcut group
         */
        $services = Capsule::table('tblhosting')->get();

        foreach ($services as $service) {
            /**
             * Get the only services with no. of X days
             */
            $x_days = Capsule::table("mod_product_xdays")->where("pid", $service->packageid)->value("value");

            if(!empty($x_days)) {
    
                $currency = getCurrency($service->userid);
                $service_date = new DateTime($service->regdate);
                $today = new DateTime();
                $date_diff = $today->diff($service_date);
                // $date_diff = $x_days+1; // testing

                /**
                 * Check the service activate more than a year
                 */
                if ($date_diff >= $x_days) {
                    $update_service = $this->update_service($service->id, $service->packageid, $currency['id']);
                    if($update_service) {
                        logActivity("Service has been update with monthly billing cycle, service id: {$service->id}");
                    } else {
                        logActivity("Unable to update the service after 1 year of free hosting, service id: {$service->id}");
                    }
                }
            }
        }
    }
    
    /**
     * Update the service
     */
    public function update_service($serviceid, $productid, $currencyid) {
        /**
         * Get the product's monthly price attached with the service
         */
        $product_monthly_price = Capsule::table('tblpricing')->where('type', 'product')->where('relid', $productid)->where('currency', $currencyid)->value('monthly');

        /**
         * Update the service amount and billing cycle
         */
        return Capsule::table('tblhosting')->where('id', $serviceid)
            ->update([
                'billingcycle' => 'Monthly',
                'amount' => $product_monthly_price,
                'nextinvoicedate' => date('Y-m-d'),
                'nextduedate' => date('Y-m-d'),
            ]);
    }

    /**
     * Get affiliate custom data values
     */
    function getAffiliateData($affiliateId){
        try{
            return Capsule::table('mod_affilate_data')
                ->where('affiliate_id', $affiliateId)
                ->first() ?? null;
        } catch (\Exception $e) {

        logActivity("Error in addon module 'affiliate' - getAffiliateData function: " . $e->getMessage());

        }
    }

    function updated_affiliate_bal($affId, $pid, $payamount) {
        try {
            $balance = Capsule::table("tblaffiliates")->where("id", $affId)->value("balance");
            
            $product = Capsule::table("tblproducts")->where("id", $pid)->first();

            if($product->affiliatepaytype == 'fixed') {
                $addedAmount = $product->affiliatepayamount;
            } elseif($product->affiliatepaytype == 'percentage') {
                $addedAmount = $product->affiliatepayamount / 100 * $payamount;
            } elseif($product->affiliatepaytype == 'none') {
                $addedAmount = 0;
            } else {
                $val = Capsule::table("tblconfiguration")->where("setting", "AffiliateEarningPercent")->value("value");
                $addedAmount = $val / 100 * $payamount;
            }

            $removeDefault = Capsule::table("tblaffiliates")->where("id", $affId)->update([
                "balance" => $balance - $addedAmount,
            ]);

            if($removeDefault) {
                $updatedBalance = Capsule::table("tblaffiliates")->where("id", $affId)->value("balance");
                return $updatedBalance;
            }
        
        } catch(Exception $e) {
            logActivity("Error in affiliate commission cron: " . $e->getMessage());
        }
    }

}


