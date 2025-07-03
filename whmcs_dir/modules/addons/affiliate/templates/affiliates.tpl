{include file=$tplVar.header}


<h2>{$LANG['affiliates_list']}</h2>

<table id="affiliateTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th>{$LANG['affiliates_name']}</th>
            <th>{$LANG['affiliates_email']}</th>
            <th>{$LANG['affiliates_balance']}</th>
            <th>{$LANG['affiliates_withdrawn']}</th>
            <th>{$LANG['affiliates_date']}</th>
        </tr>
    </thead>
</table>

<div class="selcted-itms text-center">
    <a class="btn btn-primary" id="exportAllinCSV"> <i class="fa fa-download" aria-hidden="true"></i> {$LANG['export_all_csv']}</a>
</div>