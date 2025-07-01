<?php

require_once __DIR__ . '/../modules/addons/affiliate/lib/Helper.php';
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Affiliate\Helper;

// $whmcspath = "";
// if (file_exists(dirname(__FILE__) . "/config.php"))
//     require_once dirname(__FILE__) . "/config.php";

// if (!empty($whmcspath)) {
//     require_once $whmcspath . "/init.php";
// } else {
//     require(__DIR__ . "/../init.php");
// }

// require __DIR__ . '/init.php';


$helper = new Helper();

// define('GROUPS', array(1, 2, 3)); // define groups

try {
    $helper->affiliate_program();
    
} catch (Exception $e) {
    logActivity("Exception in HostedAI Cron: " . $e->getMessage());
}
