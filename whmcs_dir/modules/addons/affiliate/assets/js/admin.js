$(document).ready(function() {
    // Affiliates table
    $('#affiliateTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "../modules/addons/affiliate/lib/Ajax/affiliates.php",
        "columns": [
            { "data": "check_box", "orderable": false, "searchable": false },
            { "data": "name"},
            { "data": "email"},
            { "data": "balance"},
            { "data": "withdrawn"},
            { "data": "date"}
        ]
    });
});





