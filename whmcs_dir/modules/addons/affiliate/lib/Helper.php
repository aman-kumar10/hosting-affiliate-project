<?php

namespace WHMCS\Module\Addon\Affiliate;
use DateTime;
use Exception;
use WHMCS\Module\Addon\Affiliate\Api;

use WHMCS\Database\Capsule;

require_once __DIR__ . '/../../../../init.php';


class Helper
{

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
                // $date_diff = $today->diff($service_date);
                $date_diff = $x_days+1; // testing

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

}


