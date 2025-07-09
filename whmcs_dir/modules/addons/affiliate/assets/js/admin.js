$(document).ready(function() {
    // Affiliates table
    let table = $('#affiliateTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "../modules/addons/affiliate/lib/Ajax/affiliates.php",
        "columns": [
            { "data": "name" },
            { "data": "email" },
            { "data": "balance" },
            { "data": "withdrawn" },
            { "data": "date" }
        ]
    });

    // When AJAX response is received, check for data length
    table.on('xhr', function (e, settings, json) {
        if (json.data && json.data.length === 0) {
            $('#exportAllinCSV').hide();
        } else {
            $('#exportAllinCSV').show();    
        }
    });

    // Export Affiliates in CSV file
    $("#exportAllinCSV").click(function() {
        let searchVal = $('#affiliateTable_filter input').val();
        window.location.href = 'addonmodules.php?module=affiliate&form_action=export_affiliates&search='+searchVal;
    });

});    



