<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Affiliate\Helper;

require __DIR__ . '/init.php';

$gid = 1;

affilate_program($gid); // define a statatic group id

function affilate_program($id)
{
    /**
     * Get the product data form database for connect with prodcut group
     */
    $products = Capsule::table('tblproducts')->where('gid', $id)->get();

    foreach ($products as $product) {
        /**
         * Get the services with Annually billing cycle
         */
        $hostings = Capsule::table('tblhosting')->where('packageid', $product->id)->where('billingcycle', 'Annually')->get();

        foreach ($hostings as $service) {

            $currency = getCurrency($service->userid);
            $service_date = new DateTime($service->regdate);
            $today = new DateTime();
            // $date_diff = $today->diff($service_date);
            $date_diff = 366;

            /**
             * Check the service activate more than a year
             */
            if ($date_diff >= 365) {

                /**
                 * Get the product's monthly price attached with the service
                 */
                $product_monthly_price = Capsule::table('tblpricing')->where('type', 'product')->where('relid', $product->id)->where('currency', $currency['id'])->value('monthly');

                /**
                 * Update the service amount and billing cycle
                 */
                Capsule::table('tblhosting')
                    ->where('id', $service->id)
                    ->update([
                        'billingcycle' => 'Monthly',
                        'amount' => $product_monthly_price,
                        'nextinvoicedate' => date('Y-m-d'),
                        'nextduedate' => date('Y-m-d'),
                    ]);
            }
        }
    }
}
