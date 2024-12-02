$(document).ready(function() {
    // Initialize Datepicker and Monthpicker
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd'
    });

    $('.monthpicker').datepicker({
        dateFormat: 'yy-mm',
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).datepicker('setDate', new Date(year, month, 1));
        },
        beforeShow: function(input, inst) {
            // Move the datepicker's calendar display to a custom div
            var $dpDiv = $(inst.dpDiv);
            $dpDiv.addClass('month-year-picker');
            setTimeout(function() {
                var $calendar = $dpDiv.find('.ui-datepicker-calendar');
                if ($calendar.length) {
                    $calendar.hide();
                }
            }, 0);
        }
    });

    // Handle Total Collection button click
    $('#totalCollectionButton').on('click', function() {
        $('#totalCollectionModal').modal('show');
        fetchCollectionData('', ''); // Default fetch with empty values
    });

    // Handle Collection Type change
    $('#collectionType').on('change', function() {
        if ($(this).val() === 'date') {
            $('#dateGroup').show();
            $('#monthGroup').hide();
        } else {
            $('#dateGroup').hide();
            $('#monthGroup').show();
        }
    });

    // Handle Total Collection form submit
    $('#totalCollectionForm').on('submit', function(e) {
        e.preventDefault();
        var type = $('#collectionType').val();
        var date = type === 'date' ? $('#collectionDate').val() : '';
        var month = type === 'month' ? $('#collectionMonth').val() : '';
        fetchCollectionData(date, month);
    });

    // Fetch collection data from server
    function fetchCollectionData(date, month) {
        $.ajax({
            url: 'fetch_collection.php',
            type: 'POST',
            data: { date: date, month: month },
            success: function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    $('#collectionResult').html('<h5>Total Collection: ' + response.collection + '</h5>');
                } else {
                    $('#collectionResult').html('<h5>No collection data found.</h5>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('An error occurred while fetching collection data.');
            }
        });
    }
});
