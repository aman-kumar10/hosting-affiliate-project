<?php

use WHMCS\Database\Capsule;
require __DIR__ . '/init.php';

$gid = 1;

affilate_program($gid);

function affilate_program($id) {
    $products = Capsule::table('tblproducts')->where('gid', $id)->get();
    
    foreach ($products as $product) {
        $hostings = Capsule::table('tblhosting')
            ->where('packageid', $product->id)
            ->where('billingcycle', 'Annually')
            ->get();

        foreach ($hostings as $service) {

            $currency = getCurrency($service->userid);
            $service_date = new DateTime($service->regdate);
            $today = new DateTime();
            // $date_diff = $today->diff($service_date);
            $date_diff = 366;
            

            if ($date_diff >= 365) {
                // echo "Updating Service ID: {$service->id} (Client ID: {$service->userid})\n";

                $product_monthly_price = Capsule::table('tblpricing')
                    ->where('type', 'product')
                    ->where('relid', $product->id)
                    ->where('currency', $currency['id'])
                    ->value('monthly');

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
