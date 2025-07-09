<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Affiliate\Helper;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly!");
}

/**
 * Display a custom tab with a custom form to submit affiliates X days and values
 * Display a custom input field with each product to submit product billing X days
 */
add_hook('AdminAreaHeaderOutput', 1, function ($vars) {
    try{
        $helper  = new Helper;

        // Execute only when on the Affiliate page 
        if (isset($vars['filename']) && $vars['filename'] == 'affiliates') {
            global $whmcs;

            $success = false;
            $affiliate_id = $vars['affiliateId'];

            // Handle custom affiliate resquest data
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($whmcs->get_req_var('x_days_no'))) {
                Capsule::table('mod_affilate_data')->updateOrInsert(
                    ['affiliate_id' => $whmcs->get_req_var('Affiliate_id')],
                    [
                        'affiliate_id' => $whmcs->get_req_var('Affiliate_id'),
                        'x_days_no' => $whmcs->get_req_var('x_days_no'),
                        'amount' => $whmcs->get_req_var('amount'),
                        'affiliate_type' => $whmcs->get_req_var('affiliate_type')
                    ]
                );
                $success = true;
            }

            $getAffiliateData = null;
            if (!empty($affiliate_id)) {
                $getAffiliateData = $helper->getAffiliateData($affiliate_id); // get affiliate data
            }

            $successMessage = $success ? "<div class='alert alert-success'>Affiliate commission data saved successfully.</div>" : "";

            $x_days = $getAffiliateData ? htmlspecialchars($getAffiliateData->x_days_no) : "";

            // Generate affiliate type option
            $affiliateTypeOptions = ['fixed', 'percentage'];
            $affiliateTypeSelectHtml = "<option value=''>-- Select Affiliate Type --</option>";
            foreach ($affiliateTypeOptions as $opt) {
                $selected = ($getAffiliateData && $getAffiliateData->affiliate_type === $opt) ? "selected" : "";
                $affiliateTypeSelectHtml .= "<option value='$opt' $selected>" . ucfirst($opt) . "</option>";
            }

            $amountValue = $getAffiliateData ? htmlspecialchars($getAffiliateData->amount) : "";

            $html = <<<HTML
            <style>
                form#affiliateSettingsForm .form-group {
                    display: flex;
                    margin-bottom: 15px;
                }
                form#affiliateSettingsForm {
                    max-width: 850px;
                    width: 100%;
                    margin: 0 auto;
                    box-shadow: 0 0 10px #b9b9b9;
                    padding: 30px;
                    border-radius: 5px;
                    margin-top: 20px !important;
                    margin-bottom: 20px !important;
                    background: #f9f9f9;
                }
                form#affiliateSettingsForm .form-group label {
                    white-space: nowrap;
                    width: 30%;
                    margin-right: 10px;
                }
                .affiliate-form-btn {
                    text-align: center;
                }
            </style>

            <script>
            $(document).ready(function () {
                var hash = window.location.hash;
                if (hash.startsWith("#tab=")) {
                    var openTabId = hash.split("=")[1];
                    setTimeout(function () {
                        $("#tabLink" + openTabId).tab("show");
                    }, 300);
                }

                var tabCount = $(".nav-tabs.admin-tabs > li").not(".dropdown").length;
                var dataTabId = tabCount + 1;

                var affilateLi = $("<li><a class='tab-top' href='#tab" + dataTabId + "' role='tab' data-toggle='tab' id='tabLink" + dataTabId + "' data-tab-id='" + dataTabId + "'>Custom Affiliate Tab</a></li>");
                $(".nav-tabs.admin-tabs").append(affilateLi);

                // Create a form to setup the Commision for Affiliates
                var newTabContent = $("<div class='tab-pane' id='tab" + dataTabId + "'>" +
                    `$successMessage` +
                    "<div id='errorBoxContainer'></div>" +
                    "<form id='affiliateSettingsForm' method=\\"post\\" action=\\"\\">" +
                        "<div class='form-group'>" +
                            "<label for='x_days_no" + dataTabId + "'>No. of X days</label>" +
                            "<input type='text' class='form-control' id='x_days_no" + dataTabId + "' name='x_days_no' placeholder='Enter number of X days' value='$x_days' />" +
                        "</div>" +
                        "<div class='form-group'>" +
                            "<label for='affiliateType" + dataTabId + "'>Affiliate Type</label>" +
                            "<select class='form-control' id='affiliateType" + dataTabId + "' name='affiliate_type'>" +
                                `$affiliateTypeSelectHtml` +
                            "</select>" +
                        "</div>" +
                        "<input type='hidden' name='Affiliate_id' value='$affiliate_id' />" +
                        "<div class='form-group'>" +
                            "<label for='amount" + dataTabId + "'>Amount</label>" +
                            "<input type='text' class='form-control' id='amount" + dataTabId + "' name='amount' placeholder='Enter amount' value='$amountValue' />" +
                        "</div>" +
                        "<div class='affiliate-form-btn'>" +
                            "<button type='submit' class='btn btn-primary'>Submit</button>" +
                        "</div>" +
                    "</form>" +
                "</div>");

                $(".tab-content").append(newTabContent);

                $(document).on("click", "#tabLink" + dataTabId, function () {
                    window.location.hash = "tab=" + dataTabId;
                });

                $(document).on("submit", "#affiliateSettingsForm", function (e) {
                    var billing = $("#x_days_no" + dataTabId).val();
                    var amount = $("#amount" + dataTabId).val().trim();
                    var affiliateType = $("#affiliateType" + dataTabId).val().trim();
                    var errorBox = $("#tab" + dataTabId + " #errorBoxContainer");
                    errorBox.html("");

                    var hasError = false;

                    if (billing === "") {
                        errorBox.append("<div class='alert alert-danger'>Please enter X days number.</div>");
                        hasError = true;
                    }

                    if (amount === "") {
                        errorBox.append("<div class='alert alert-danger'>Please enter an amount.</div>");
                        hasError = true;
                    } else if (!/^\d+$/.test(amount)) {
                        errorBox.append("<div class='alert alert-danger'>Amount must be an integer value.</div>");
                        hasError = true;   
                    } else if (parseInt(amount) < 1) {
                        errorBox.append("<div class='alert alert-danger'>Amount must be greater than or equal to 1.</div>");
                        hasError = true;
                    }
                    if (affiliateType === "") {
                        errorBox.append("<div class='alert alert-danger'>Please select a affiliate type.</div>");
                        hasError = true;
                    }

                    if (hasError) {
                        e.preventDefault();
                    }

                    // Hide alert message after 15 seconds
                    setTimeout(function () {
                        $(".alert").fadeOut("slow", function () {
                            $(this).remove();
                        });
                    }, 15000);
                });

                // Hide validation alert messages after 15 seconds 
                setTimeout(function () {
                    $(".alert").fadeOut("slow", function () {
                        $(this).remove();
                    });
                }, 15000);
            });
            </script>
        HTML;

            return $html;
        }

        // Execute only when on the Products page
        if(isset($vars['filename']) && $vars['filename'] == 'configproducts' && isset($_REQUEST['id']))  {

            $x_days = Capsule::table('mod_product_xdays')->where('pid', $_REQUEST['id'])->value('value');
    
            $days = $x_days ?? '';
    
            $html = <<<HTML
            <style>
                #x_days_input .fieldarea .col-sm-7 p {
                    margin-top: 5px;
                }
                #x_days_input .fieldarea .col-sm-7  {
                    padding-left: 0 !important;
                }
            </style>
            <script>
                $(document).ready(function () {
                    
                    // Custom input field for products X days
                    var inputField = `
                        <tr id='x_days_input'>
                            <td class='fieldlabel'>No. of X Days</td>
                            <td class='fieldarea'>
                                <div class="row">
                                    <div class='col-sm-3'><input type='number' size='40' name='x_days' value="{$days}" class='form-control input-400 input-inline' id='inputProductXdays'></div>
                                    <div class='col-sm-7'><p>Enter the number of days for free hosting service.</p></div>
                                </div>
                            </td>
                        </tr>
                    `;
    
                    var \$table = $("#frmProductEdit #tab1.tab-pane.active table");
                    if (\$table.length) {
                        \$table.append(inputField);
                    }
                });
            </script>
            HTML;
    
            return $html;
        }

    } catch (\Exception $e) {
        logActivity("Error in addon module 'affiliate' - AdminAreaHeadOutput function" . $e->getMessage());
    }
});


/**
 * Handle product's
 */
add_hook("ProductEdit", 1, function($vars) {
    // handle the form request to insert product X days
    if(isset($_POST['x_days'])) {
        Capsule::table('mod_product_xdays')->updateOrInsert(
            ['pid' => $vars['pid'], 'gid' => $vars['gid']], 
            ['value' => $_POST['x_days']]
        );
    }
});


/**
 * Add or manage affiliate commission on InvoicePaid
 */
add_hook("InvoicePaid", 1, function($vars) {
    try {
        $helper  = new Helper;

        $invoiceid = $vars['invoiceid']; 
        $invoice_amount = $vars['invoice']->total; 

        $invoiceItem = Capsule::table("tblinvoiceitems")->where("type", "Hosting")->where("invoiceid", $invoiceid)->get(); 

        foreach($invoiceItem as $item) { 

            $service = Capsule::table("tblhosting")->where("id", $item->relid)->first(); 
            $product_Xdays = Capsule::table("mod_product_xdays")->where("pid", $service->packageid)->value("value"); 

            if($product_Xdays) { 
                $date = date('Y-m-d', strtotime("+{$product_Xdays} days", strtotime($service->regdate))); 
                Capsule::table('tblhosting')->where("id", $service->id)->update([ 
                    'nextduedate' => $date, 
                ]); 
            } 

            $affiliate_data = Capsule::table('tblaffiliatesaccounts') 
                ->join('mod_affilate_data', 'tblaffiliatesaccounts.affiliateid', '=', 'mod_affilate_data.affiliate_id')
                ->where('tblaffiliatesaccounts.relid', $service->id)
                ->select('tblaffiliatesaccounts.*', 'mod_affilate_data.*')
                ->first();

            $affiliate_Xdays = $affiliate_data->x_days_no; 
            $total_days = $product_Xdays + $affiliate_Xdays; 
            // $total_days = 0; // testing

            if($affiliate_data) { 
                // Get the auto updated affiliate commission
                $balance = $helper->updated_affiliate_bal($affiliate_data->affiliateid, $service->packageid, $invoice_amount);   

                // Days difference
                $service_date = new DateTime($service->regdate); 
                $today = new DateTime(); 
                $date_difference = $today->diff($service_date)->days; 

                // Get the commission amount
                if($affiliate_data->affiliate_type == 'fixed') { 
                    $add_amount = $affiliate_data->amount; 
                } else { 
                    $add_amount = $affiliate_data->amount / 100 * $invoice_amount; 
                } 

                // update the affiliate balance with custom commission value
                if($date_difference >= $total_days) { 
                    Capsule::table("tblaffiliates")->where("id", $affiliate_data->affiliateid)->update([ 
                        "balance" => $balance + $add_amount, 
                    ]); 
                } 
            } 
        } 
 
    } catch(Exception $e) {
        logActivity("Error in affiliate commision: " . $e->getMessage());
    }
});

