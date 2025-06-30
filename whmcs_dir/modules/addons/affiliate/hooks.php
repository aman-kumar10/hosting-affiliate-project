<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly!");
}


// add_hook("AffiliateClickthru", 1, function($vars) {
//     echo "<pre>"; print_r($vars); die;
// });

// add_hook("ClientAreaPageAffiliates", 1, function($vars) { 
//     // echo "<pre>"; print_r($vars); die; 
// });

// add_hook('AffiliateActivation', 1, function($vars) {
//     echo "<pre>"; print_r($vars); die; 
// });


// function getAffiliateSettings() {
//     $moduleSettings = Capsule::table('tbladdonmodules')->where('module', 'custom_affiliate')->get();
//     $settings = [];
//     foreach ($moduleSettings as $setting) {
//         $settings[$setting->setting] = $setting->value;
//     }
//     return $settings;
// }


add_hook('AdminAreaHeadOutput', 1, function ($vars) {

    try{
       

        if (isset($vars['filename']) && $vars['filename'] == 'affiliates') {
             affiliateData();
            global $whmcs;

            $success = false;
            $affiliate_id = $vars['affiliateId'];

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($whmcs->get_req_var('billing_cycle'))) {
                Capsule::table('mod_affilate_data')->updateOrInsert(
                    ['affiliate_id' => $whmcs->get_req_var('Affliate_id')],
                    [
                        'affiliate_id'    => $whmcs->get_req_var('Affliate_id'),
                        'billing_cycle'   => $whmcs->get_req_var('billing_cycle'),
                        'amount'          => $whmcs->get_req_var('amount'),
                        'affiliate_type'  => $whmcs->get_req_var('affiliate_type')
                    ]
                );
                $success = true;
            }

            $getAffiliateData = null;
            if (!empty($affiliate_id)) {
                $getAffiliateData = getAffiliateData($affiliate_id); // should return object with billing_cycle, amount
            }

            $successMessage = $success ? "<div class='alert alert-success'>Affiliate commission data saved successfully.</div>" : "";

            // Generate billing cycle options
            $billingOptions = ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'];
            $billingSelectHtml = "<option value=''>-- Select Billing Cycle --</option>";
            foreach ($billingOptions as $opt) {
                $selected = ($getAffiliateData && $getAffiliateData->billing_cycle === $opt) ? "selected" : "";
                $billingSelectHtml .= "<option value='$opt' $selected>" . ucfirst($opt) . "</option>";
            }

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

                var affilateLi = $("<li><a class='tab-top' href='#tab" + dataTabId + "' role='tab' data-toggle='tab' id='tabLink" + dataTabId + "' data-tab-id='" + dataTabId + "'>Affiliate Commission</a></li>");
                $(".nav-tabs.admin-tabs").append(affilateLi);

                var newTabContent = $("<div class='tab-pane' id='tab" + dataTabId + "'>" +
                    `$successMessage` +
                    "<div id='errorBoxContainer'></div>" +
                    "<form id='affiliateSettingsForm' method=\\"post\\" action=\\"\\">" +
                        "<div class='form-group'>" +
                            "<label for='billingCycle" + dataTabId + "'>Billing Cycle</label>" +
                            "<select class='form-control' id='billingCycle" + dataTabId + "' name='billing_cycle'>" +
                                `$billingSelectHtml` +
                            "</select>" +
                        "</div>" +
                        "<div class='form-group'>" +
                            "<label for='affiliateType" + dataTabId + "'>Affiliate Type</label>" +
                            "<select class='form-control' id='affiliateType" + dataTabId + "' name='affiliate_type'>" +
                                `$affiliateTypeSelectHtml` +
                            "</select>" +
                        "</div>" +
                        "<input type='hidden' name='Affliate_id' value='$affiliate_id' />" +
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
                    var billing = $("#billingCycle" + dataTabId).val();
                    var amount = $("#amount" + dataTabId).val().trim();
                    var affiliateType = $("#affiliateType" + dataTabId).val().trim();
                    var errorBox = $("#tab" + dataTabId + " #errorBoxContainer");
                    errorBox.html("");

                    var hasError = false;

                    if (billing === "") {
                        errorBox.append("<div class='alert alert-danger'>Please select a billing cycle.</div>");
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

                    // Hide alerts after 15 seconds
                    setTimeout(function () {
                        $(".alert").fadeOut("slow", function () {
                            $(this).remove();
                        });
                    }, 15000);
                });

                // Also hide alerts if not from validation
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
    } catch (\Exception $e) {
        logActivity("Error in addon module 'affiliate' - AdminAreaHeadOutput function" . $e->getMessage());
    }
});


function affiliateData()
{
    try{
        if (!Capsule::Schema()->hasTable('mod_affilate_data')) {
            Capsule::schema()->create(
                'mod_affilate_data',
                function ($table) {
                    $table->increments('id');
                    $table->string('affiliate_id');
                    $table->string('billing_cycle');
                    $table->string('affiliate_type');
                    $table->string('amount');
                }
            );
        }
    } catch (\Exception $e) {
        logActivity("Error in addon module 'affiliate' - affiliateData function" . $e->getMessage());
    }
}

function getAffiliateData($affiliateId)
{
    try{
        return Capsule::table('mod_affilate_data')
            ->where('affiliate_id', $affiliateId)
            ->first() ?? null;
    } catch (\Exception $e) {

       logActivity("Error in addon module 'affiliate' - getAffiliateData function: " . $e->getMessage());

    }
}
