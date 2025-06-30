<link rel="stylesheet" href="../modules/addons/affiliate/assets/css/admin.css">
<script src="../modules/addons/affiliate/assets/js/admin.js"></script>

<div class="add_hdr">

    <div class="add_nav">
        <ul>
            <li class="header-tab"><a href="addonmodules.php?module=affiliate" class="ad_home {if $tplVar['tab'] =='index'}active {/if} "><i class="fa fa-user" aria-hidden="true"></i> {$LANG['tab_clients']}</a></li>
            <li class="header-tab"><a href="addonmodules.php?module=affiliate&action=settings" class="ad_home {if $tplVar['tab'] =='settings'}active {/if} "><i class="fas fa-file-invoice"></i> {$LANG['tab_settings']}</a></li>
        </ul>    
    </div>

    {if $tplVar['tab'] == 'affiliate_logs'}
    <div class="add_nav">
        <ul>
            <li class="delete-logs"><a href="#" id="deleteCRMLogs" class="btn btn-default"> {$LANG['tab_reset_logs']} </a></li>
        </ul>    
    </div>
    {/if}
</div>