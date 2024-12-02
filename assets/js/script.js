$(document).ready(function () {
    // Initialize DataTables
    $('#khataTable').DataTable({
        paging: false, // Disable pagination
        searching: true, // Enable search
        info: false // Disable the info text
    });

    $('#historyTable').DataTable({
        paging: false,
        searching: false
    });

    $('#bakayaTable').DataTable({
        paging: false,
        searching: false
    });

    // Clear form data when modal is closed
    $('#addUserModal').on('hidden.bs.modal', function () {
        $('#userForm')[0].reset();
        $('#edit_id').val('');
    });

    // Handle edit button click
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var mobileNumber = $(this).data('mobile_number');
        var paiseDiye = $(this).data('paise_diye');
        var paiseLene = $(this).data('paise_lene');
        var date = $(this).data('date');

        // Set the form fields with the existing user data
        $('#edit_id').val(id);
        $('#name').val(name);
        $('#mobile_number').val(mobileNumber);
        $('#paise_diye').val(paiseDiye);
        $('#paise_lene').val(paiseLene);
        $('#date').val(date);

        // Change the modal title to "Edit User Details"
        $('#addUserModalLabel').text('Edit User Details');

        // Show the modal
        $('#addUserModal').modal('show');
        // Initialize Datepicker
        
    // Initialize the datepicker for the edit user modal
    $('#date').datepicker({ dateFormat: "yy-mm-dd" });
 
    
    });


    // Automatically open Khata section on page load
    $('#khataSection').show();

    // Initialize Datepicker
  $(function() {
    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd" // Ensure the date format is YYYY-MM-DD
    });
    });


    // Show Khata section
    $('#khataButton').on('click', function () {
        $('#khataSection').toggle();
    });

    // Handle calendar button click
    $(document).on('click', '.btn-calendar', function () {
        var userId = $(this).data('id');
        $('#calendarModal').modal('show');

        // Initialize the calendar with the current month
        var currentDate = new Date();
        generateCalendar(currentDate, userId);
    });

    // Handle previous month button click
    $('#prevMonth').on('click', function () {
        var currentMonth = $('#calendar').data('month');
        var currentYear = $('#calendar').data('year');
        var userId = $('#calendar').data('userId');

        var newDate = new Date(currentYear, currentMonth - 1, 1);
        generateCalendar(newDate, userId);
    });

    // Handle next month button click
    $('#nextMonth').on('click', function () {
        var currentMonth = $('#calendar').data('month');
        var currentYear = $('#calendar').data('year');
        var userId = $('#calendar').data('userId');

        var newDate = new Date(currentYear, currentMonth + 1, 1);
        generateCalendar(newDate, userId);
    });


    // Generate calendar with repayment events
    function generateCalendar(date, userId) {
        var year = date.getFullYear();
        var month = date.getMonth();
        var firstDay = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();

        $('#calendar').data('month', month);
        $('#calendar').data('year', year);
        $('#calendar').data('userId', userId);

        $('#calendarTitle').text(date.toLocaleString('default', { month: 'long' }) + ' ' + year);

        var calendar = $('#calendar');
        calendar.empty();

        // Add empty cells for days before the first day of the month
        for (var i = 0; i < firstDay; i++) {
            calendar.append('<div class="day"></div>');
        }

        // Fetch repayment data and generate calendar
        $.ajax({
            url: 'fetch_calendar.php', // Update the PHP file path if needed
            type: 'POST',
            data: { id: userId, month: month + 1, year: year },
            success: function (response) {
                response = JSON.parse(response);
                if (response.success) {
                    var events = response.data;

                    // Add cells for each day of the month
                    for (var day = 1; day <= daysInMonth; day++) {
                        var dayCell = $('<div class="day"></div>');
                        dayCell.append('<div class="date">' + day + '</div>');

                        // Add events for the day
                        var eventFound = false;
                        events.forEach(function (event) {
                            var eventDate = new Date(event.start);
                            if (eventDate.getDate() === day && eventDate.getMonth() === month && eventDate.getFullYear() === year) {
                                dayCell.append('<div class="event">Paid: ' + event.title + '</div>');
                                eventFound = true;
                            }
                        });

                        if (!eventFound) {
                            dayCell.append('<div class="not-paid">Not Paid</div>');
                        }

                        calendar.append(dayCell);
                    }
                } else {
                    alert('Failed to fetch calendar data.');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error fetching calendar data:', error);
            }
        });
    }

    // Handle history button click
    $(document).on('click', '.btn-history', function () {
        var id = $(this).data('id');

        $.ajax({
            url: 'filter_history.php',
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
            },
            error: function (xhr, status, error) {
                console.error('Error fetching history data:', error);
            }
        });
    });

    // Handle delete history button click
    $(document).on('click', '.btn-delete-history', function () {
        var historyId = $(this).data('id');
        if (confirm('Are you sure you want to delete this history record?')) {
            $.ajax({
                url: 'delete_history.php',
                type: 'POST',
                data: { id: historyId },
                success: function (response) {
                    response = JSON.parse(response);
                    if (response.success) {
                        alert('History record deleted successfully.');
                        $('#historyTable').DataTable().ajax.reload(); // Refresh the table data
                    } else {
                        alert('Failed to delete history record.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error deleting history record:', error);
                }
            });
        }
    });

    // Handle delete button click
    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        $('#deleteUserId').val(id);
        $('#deleteUserModal').modal('show');
    });

    // Handle delete form submission
    $('#deleteUserForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'delete_user.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert('User deleted successfully.');
                    location.reload(); // Refresh the page
                } else {
                    alert(response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error deleting user:', error);
            }
        });
    });

    // Open the modal on button click
    $('#openDailyMonthModalButton').on('click', function() {
        $('#dailyMonthModal').modal('show');
    });

    // Initialize month picker in the modal
    $('#dailyModalExportMonth').datepicker({
        dateFormat: "mm-yy",
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        onClose: function(dateText, inst) { 
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).val($.datepicker.formatDate('mm-yy', new Date(year, month, 1)));
        }
    });

    // Handle export button click in the modal
    $('#dailyExportSelectedMonthButton').on('click', function() {
        var exportOption = $('input[name="dailyExportOption"]:checked').val();
        var url = 'export_daily_user_histories.php'; // Adjust this to your actual export script

        if (exportOption === 'specific') {
            var selectedMonth = $('#dailyModalExportMonth').val();
            if (!selectedMonth) {
                alert('Please select a month before exporting.');
                return;
            }
            url += '?month=' + encodeURIComponent(selectedMonth);
        }

        console.log('Export Option:', exportOption); // Debugging line
        window.location.href = url;
        $('#dailyMonthModal').modal('hide');
    });

    // Toggle month picker visibility based on selected option
    $('input[name="dailyExportOption"]').on('change', function() {
        if ($(this).val() === 'specific') {
            $('#dailyMonthPickerContainer').show();
        } else {
            $('#dailyMonthPickerContainer').hide();
        }
    });
});
