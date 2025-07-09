<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Affiliate\Admin\AdminDispatcher;
use WHMCS\Module\Addon\Affiliate\Helper;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/* 
 * Define module configuration options 
 */
function affiliate_config() {

    return [
        'name' => 'Hosting Affiliate',
        'description' => 'This addon module used to manage the affiliates for active hosting.',
        'author' => 'WGS',
        'language' => 'english',
        'version' => '1.0',
        'fields' => [
            'module_description' => [
                'FriendlyName' => 'Description',
                'Type' => 'textarea',
                'Rows' => '3',
                'Cols' => '60',
                'Description' => 'Write about the module',
            ],
            'delete_database' => [
                'FriendlyName' => 'Delete Database',
                'Type' => 'yesno',
                'Description' => "Enable this to delete database on module deactivation.",
            ],
        ]
    ];
}

/* 
 * Module Activation
 */
function affilate_activate() {
    try {
        // Create custom table to manage affiliate commission and data
        if (!Capsule::Schema()->hasTable('mod_affilate_data')) {
            Capsule::schema()->create(
                'mod_affilate_data',
                function ($table) {
                    $table->increments('id');
                    $table->string('affiliate_id');
                    $table->string('x_days_no');
                    $table->string('affiliate_type');
                    $table->string('amount');
                }
            );
        }

        // Create custom table for product's billing X days 
        if (!Capsule::Schema()->hasTable('mod_product_xdays')) {
            Capsule::schema()->create(
                'mod_product_xdays',
                function ($table) {
                    $table->increments('id');
                    $table->string('gid');
                    $table->string('pid');
                    $table->string('value');
                }
            );
        }

        return [
            'status' => 'success',
            'description' => 'Module activated successfully',
        ];

    } catch (\Exception $e) {
        return [
            'status' => "error",
            'description' => 'Unable to activate module: ' . $e->getMessage(),
        ];
    }
} 

/*
 * Module Deactivation
 */
function affilate_deactivate() {
    try {
        // Delete the custom database if delete database is enabled in module configuration
        $del_database = Capsule::table("tbladdonmodules")->where("module", "affiliate")->where("setting", "delete_database")->value("value");
        if($del_database == "on") {
            Capsule::schema()->dropIfExists('mod_affilate_data');
            Capsule::schema()->dropIfExists('mod_product_xdays');
        }
        return [
            'status' => 'success',
            'description' => 'Module deactivated successfully',
        ];
    } catch (\Exception $e) {
        return [
            'status' => "error",
            'description' => 'Unable to deactivate module: ' . $e->getMessage(),
        ];
    }
} 

/*
 * Module Admin Output Dispatcher
 * Routes admin actions through AdminDispatcher class
 */
function affiliate_output($vars) {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'affiliates';

    $dispatcher = new AdminDispatcher();
    $dispatcher->dispatch($action, $vars);
}

