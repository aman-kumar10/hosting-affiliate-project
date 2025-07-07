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
            'access_hash' => [
                'FriendlyName' => 'Access Token',
                'Type' => 'textarea',
                'Rows' => '3',
                'Cols' => '60',
                'Description' => 'Enter Access hash token here',
            ],
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
                    $table->string('x_days');
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

        // Create custom table for updated next due for service 
        if (!Capsule::Schema()->hasTable('mod_updated_service_duedate')) {
            Capsule::schema()->create(
                'mod_updated_service_duedate', 
                function ($table) {
                    $table->increments('id');
                    $table->string('serviceid');
                    $table->string('pid');
                    $table->date('updated_date');
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

