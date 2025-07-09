<?php

namespace WHMCS\Module\Addon\Affiliate;

use DateTime;
use Exception;
use WHMCS\Module\Addon\Affiliate\Api;

use WHMCS\Database\Capsule;

require_once __DIR__ . '/../../../../init.php';


class Helper
{

    /**
     * Get the affiliates data
     */
    public function getAffiliatesDataTable($start, $length, $search = '')
    {
        // Get affiliate clients
        $query = Capsule::table('tblaffiliates')
            ->join('tblclients', 'tblaffiliates.clientid', '=', 'tblclients.id')
            ->select(
                'tblaffiliates.id as affiliate_id',
                'tblaffiliates.date',
                'tblaffiliates.balance',
                'tblaffiliates.withdrawn',
                'tblclients.id as client_id',
                'tblclients.firstname',
                'tblclients.lastname',
                'tblclients.email'
            );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(tblclients.firstname, ' ', tblclients.lastname) LIKE ?", ["%$search%"])
                ->orWhere('tblclients.email', 'like', "%$search%")
                ->orWhere('tblaffiliates.date', 'like', "%$search%")
                ->orWhere('tblaffiliates.balance', 'like', "%$search%")
                ->orWhere('tblaffiliates.withdrawn', 'like', "%$search%");
            });
        }

        $affiliates = $query->skip($start)->take($length)->get();

        $data = [];
        foreach ($affiliates as $affiliate) {
            $currency = getCurrency($affiliate->client_id);
            $data[] = [
                'name' => '<a href="affiliates.php?action=edit&id=' . $affiliate->affiliate_id . '">' . $affiliate->firstname . ' ' . $affiliate->lastname . '</a>',
                'email' => '<a href="clientssummary.php?userid=' . $affiliate->client_id . '">' . $affiliate->email . '</a>',
                'balance' => $currency['prefix'] . $affiliate->balance . " " . $currency['suffix'],
                'withdrawn' => $currency['prefix'] . $affiliate->withdrawn . " " . $currency['suffix'],
                'date' => $affiliate->date,
            ];
        }

        return $data;
    }

    public function getAffiliatesCount($search = '')
    {
        $query = Capsule::table('tblaffiliates')
            ->join('tblclients', 'tblaffiliates.clientid', '=', 'tblclients.id')
            ->select(
                'tblaffiliates.id as affiliate_id',
                'tblaffiliates.date',
                'tblaffiliates.balance',
                'tblaffiliates.withdrawn',
                'tblclients.id as client_id',
                'tblclients.firstname',
                'tblclients.lastname',
                'tblclients.email'
            );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(tblclients.firstname, ' ', tblclients.lastname) LIKE ?", ["%$search%"])
                ->orWhere('tblclients.email', 'like', "%$search%")
                ->orWhere('tblaffiliates.date', 'like', "%$search%")
                ->orWhere('tblaffiliates.balance', 'like', "%$search%")
                ->orWhere('tblaffiliates.withdrawn', 'like', "%$search%");
            });
        }

        return $query->count();
    }

    /**
     * Retrieve the service and, after completing one year of free hosting,
     * Automatically update the billing cycle to monthly.
     */
    public function affiliate_program(){

        // Get the product data form database for connect with prodcut group
        $services = Capsule::table('tblhosting')->where("billingcycle", "Annually")->get();

        foreach ($services as $service) {

            // Get the only services with no. of X days
            $x_days = Capsule::table("mod_product_xdays")->where("pid", $service->packageid)->value("value");

            if(!empty($x_days)) {
    
                $currency = getCurrency($service->userid);
                $service_date = new DateTime($service->regdate);
                $today = new DateTime();
                $date_diff = $today->diff($service_date)->days;
                // $date_diff = $x_days+1; // testing

                // Check the service activate more than a year
                if ($date_diff >= $x_days) {
                    $update_service = $this->update_service($service->id, $service->packageid, $currency['id']);
                    if($update_service) {
                        logActivity("Service has been updated with monthly billing cycle, service id: {$service->id}"); 
                    } else {
                        logActivity("unable to updated the service after 1 year of free hosting, service id: {$service->id}"); 
                    }
                }
            }
        }
    }
    
    /**
     * Update the service billing cycle and amount
     */
    public function update_service($serviceid, $productid, $currencyid) {

        // Get the product's monthly price attached with the service
        $product_monthly_price = Capsule::table('tblpricing')->where('type', 'product')->where('relid', $productid)->where('currency', $currencyid)->value('monthly');

        // Update the service amount and billing cycle
        return Capsule::table('tblhosting')->where('id', $serviceid)
            ->update([
                'billingcycle' => 'Monthly',
                'amount' => $product_monthly_price,
            ]);
    }

    /**
     * Get affiliate custom data values
     */
    public function getAffiliateData($affiliateId){
        try{
            // get the custom values of affiliate
            return Capsule::table('mod_affilate_data')
                ->where('affiliate_id', $affiliateId)
                ->first() ?? null;
        } catch (\Exception $e) {

        logActivity("Error in addon module 'affiliate' - getAffiliateData function: " . $e->getMessage());

        }
    }

    /**
     * Get affiliate auto updated balance and commission
     */
    public function updated_affiliate_bal($affId, $pid, $payamount) {
        try {
            $product = Capsule::table("tblproducts")->where("id", $pid)->first();

            // get the commission amount if used by whmcs default or in product affiliates 
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

            $updateAmount = Capsule::table("tblaffiliates")->where("id", $affId)->value("balance") - $addedAmount;

            // update the affiliate balance by removing auto updated balance
            Capsule::table("tblaffiliates")->where("id", $affId)->update([
                "balance" => $updateAmount,
            ]);
        
            return $updateAmount;
        
        } catch(Exception $e) {
            logActivity("Error in affiliate commission: " . $e->getMessage());
        }
    }

    /**
     * Export the Affiliates in CSV file
     */
    public function export_affiliates($search)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="affiliates.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // CSV file headings
        fputcsv($output, ['Client Name', 'Email', 'Balance', 'Withdrawn', 'Signup Date']);

        // Get the affiliates clients
        $query = Capsule::table('tblaffiliates')
            ->join('tblclients', 'tblaffiliates.clientid', '=', 'tblclients.id')
            ->select(
                'tblaffiliates.id as affiliate_id',
                'tblclients.id as client_id',
                'tblclients.firstname',
                'tblclients.lastname',
                'tblclients.email',
                'tblaffiliates.balance',
                'tblaffiliates.withdrawn',
                'tblaffiliates.date'
            );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(tblclients.firstname, ' ', tblclients.lastname) LIKE ?", ["%$search%"])
                    ->orWhere('tblclients.email', 'like', "%$search%")
                    ->orWhere('tblaffiliates.date', 'like', "%$search%")
                    ->orWhere('tblaffiliates.balance', 'like', "%$search%")
                    ->orWhere('tblaffiliates.withdrawn', 'like', "%$search%");
            });
        }

        $affiliates = $query->get();

        foreach ($affiliates as $affiliate) {
            $currency = getCurrency($affiliate->client_id);

            fputcsv($output, [
                $affiliate->firstname . ' ' . $affiliate->lastname,
                $affiliate->email,
                $currency['prefix'].$affiliate->balance.''.$currency['suffix'],
                $currency['prefix'].$affiliate->withdrawn.''.$currency['suffix'],
                $affiliate->date,
            ]);
        }

        fclose($output);
        exit;
    }

}


