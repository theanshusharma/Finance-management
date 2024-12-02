$(document).ready(function () {
    // Initialize DataTables
    $('#khataTable').DataTable({
        paging: false, // Disable pagination
        searching: true, // Enable search (optional)
        info: false // Disable the info text (optional)
    });

    $('#historyTable').DataTable({
        paging: false,
        searching: false
    });

    // Automatically open Khata modal on page load
    $('#khataSection').show();

    // Initialize Datepicker
    $('.datepicker').datepicker({
        dateFormat: 'dd-mm-yy'
    });

    $('.monthpicker').datepicker({
        dateFormat: 'mm-yy',
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        onClose: function (dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).datepicker('setDate', new Date(year, month, 1));
        },
        beforeShow: function (input, inst) {
            var $dpDiv = $(inst.dpDiv);
            $dpDiv.addClass('month-year-picker');
            setTimeout(function () {
                var $calendar = $dpDiv.find('.ui-datepicker-calendar');
                if ($calendar.length) {
                    $calendar.hide();
                }
            }, 0);
        }
    });

    // Show Khata section
    $('#khataButton').on('click', function () {
        $('#khataSection').toggle();
    });

    // Handle repayment button click
    $(document).on('click', '.btn-repayment', function () {
        var id = $(this).data('id');
        var mahine_ka_byaj = $(this).data('mahine_ka_byaj');

        $('#repaymentModal').modal('show');
        $('#repaymentForm').data('id', id);
        $('#repaymentForm').data('mahine_ka_byaj', mahine_ka_byaj);
    });

    // Handle repayment form submission
    $('#repaymentForm').on('submit', function (e) {
        e.preventDefault();

        var id = $(this).data('id');
        var mahine_ka_byaj = $(this).data('mahine_ka_byaj');
        var repaymentAmount = $('#repaymentAmount').val();
        var repaymentDate = $('#repaymentDate').val();

        if (repaymentAmount > 0 && repaymentAmount <= mahine_ka_byaj) {
            if (confirm('Are you sure you want to make this repayment?')) {
                $.ajax({
                    url: 'repayment_monthly.php',
                    type: 'POST',
                    data: {
                        id: id,
                        repaymentAmount: repaymentAmount,
                        repaymentDate: repaymentDate
                    },
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.success) {
                            alert('Repayment successful.');
                            location.reload();
                        } else {
                            alert('Repayment failed.');
                        }
                    }
                });
            }
        } else {
            alert('Invalid repayment amount.');
        }
    });

    // Handle history button click
    $(document).on('click', '.btn-history', function () {
        var id = $(this).data('id');

        $.ajax({
            url: 'filter_history_monthly.php',
            type: 'POST',
            data: { id: id },
            success: function (response) {
                response = JSON.parse(response);
                if (response.success) {
                    var historyTable = $('#historyTable').DataTable();
                    historyTable.clear().draw();
                    response.data.forEach(function (row) {
                        historyTable.row.add([
                            row.id,
                            row.user_id,
                            row.repayment_amount,
                            row.repayment_date,
                            '<button class="btn btn-danger btn-delete-history" data-id="' + row.id + '">Delete</button>'
                        ]).draw();
                    });
                    $('#historyModal').modal('show');
                } else {
                    alert('No repayment history found for this user.');
                }
            }
        });
    });

    // Handle delete history button click
    $(document).on('click', '.btn-delete-history', function () {
        var historyId = $(this).data('id');
        if (confirm('Are you sure you want to delete this history record?')) {
            $.ajax({
                url: 'delete_history_monthly.php',
                type: 'POST',
                data: { id: historyId },
                success: function (response) {
                    console.log('AJAX request successful:', response); // Log the response
                    response = JSON.parse(response);
                    if (response.success) {
                        alert('History record deleted successfully.');
                        location.reload();
                    } else {
                        alert('Failed to delete history record.');
                    }
                },
                error: function (xhr, status, error) {
                    console.log('AJAX request failed:', error); // Log any error
                }
            });
        }
    });

    // Handle delete button click
    $(document).on('click', '.btn-delete', function () {
        var userId = $(this).data('id');
        var row = $(this).closest('tr');

        if (confirm('Are you sure you want to delete this user?')) {
            $.ajax({
                url: 'delete_user.php',
                type: 'POST',
                data: { id: userId },
                success: function (response) {
                    console.log(response); // Log the response for debugging
                    if (response.trim() == 'success') {
                        row.remove();
                    } else {
                        alert('Failed to delete user: ' + response);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error: ' + status + error);
                }
            });
        }
    });

    // Handle Total Collection button click
    $('#totalCollectionButton').on('click', function () {
        $('#totalCollectionModal').modal('show');
        fetchCollectionData('', ''); // Default fetch with empty values
    });

    // Handle Collection Type change
    $('#collectionType').on('change', function () {
        if ($(this).val() === 'date') {
            $('#dateGroup').show();
            $('#monthGroup').hide();
        } else {
            $('#dateGroup').hide();
            $('#monthGroup').show();
        }
    });

    // Handle Total Collection form submit
    $('#totalCollectionForm').on('submit', function (e) {
        e.preventDefault();
        var type = $('#collectionType').val();
        var date = type === 'date' ? $('#collectionDate').val() : '';
        var month = type === 'month' ? $('#collectionMonth').val() : '';
        fetchCollectionData(date, month);
    });

    // Fetch collection data from server
    function fetchCollectionData(date, month) {
        $.ajax({
            url: 'fetch_collection_monthly.php',
            type: 'POST',
            data: { date: date, month: month },
            success: function (response) {
                response = JSON.parse(response);
                if (response.success) {
                    $('#collectionResult').html('<h5>Total Collection: ' + response.collection + '</h5>');
                } else {
                    $('#collectionResult').html('<h5>No collection data found.</h5>');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('An error occurred while fetching collection data.');
            }
        });
    }

    
});
