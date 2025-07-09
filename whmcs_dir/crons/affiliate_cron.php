<?php

$whmcspath = "";
 
 
if (file_exists(dirname(__FILE__) . "/config.php")) {
    require_once dirname(__FILE__) . "/config.php";
}
 
 
if (!empty($whmcspath)) {
    require_once $whmcspath . "/init.php";
    if (file_exists($whmcspath . '/modules/addons/affiliate/lib/Helper.php')) {
        require_once($whmcspath . '/modules/addons/affiliate/lib/Helper.php');
    } else {
        logActivity('Affilate  Cron error, File (/modules/addons/affiliate/lib/Helper.php) not found');
    }
} else {
    require(__DIR__ . "/../init.php");
     if (file_exists(__DIR__ . '/../modules/addons/affiliate/lib/Helper.php')) {
        require_once(__DIR__ . '/../modules/addons/affiliate/lib/Helper.php');
    } else {
        logActivity('Affilate  Cron error, File (/modules/addons/affiliate/lib/Helper.php) not found');
    }
}
 
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Affiliate\Helper;
 
 

$helper = new Helper(); 

/**
 * Cron process for hosting services
 */
try {
    /**
     * CRON Job: Automates the transition of a service's billing cycle to monthly
     * after one year of free hosting as part of the affiliate program.
     */
    logActivity('Affiliate Cron: Execution started');

    $helper->affiliate_program();

    logActivity('Affiliate Cron: Execution completed successfully');

} catch (Exception $e) {
    logActivity("Affiliate Cron Exception: " . $e->getMessage());
}