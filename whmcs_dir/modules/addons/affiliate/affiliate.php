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
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'index';

    $dispatcher = new AdminDispatcher();
    $dispatcher->dispatch($action, $vars);
}

