<?php
// Fetch repayment data for all users
$repaymentData = [];
$stmt = $conn->prepare("SELECT user_id, repayment_amount, repayment_date FROM repayments");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $repaymentData[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Repayment Calendar</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include jQuery UI CSS -->
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <!-- Include DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- Custom CSS for Calendar -->
    <style>
        .calendar {
            display: flex;
            flex-wrap: wrap;
            width: 100%;
        }
        .day {
            width: 14.28%;
            border: 1px solid #ddd;
            box-sizing: border-box;
            padding: 10px;
        }
        .date {
            font-weight: bold;
        }
        .event {
            background-color: #f0f0f0;
            margin-top: 5px;
            padding: 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Repayment Calendar</h2>
        <div id="calendar" class="calendar"></div>
    </div>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Include jQuery UI -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- Include Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Include DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize DataTables
        $('#khataTable').DataTable();
        $('#historyTable').DataTable();
        $('#bakayaTable').DataTable();

        // Repayment data embedded in the HTML
        var repaymentData = <?php echo json_encode($repaymentData); ?>;

        // Handle calendar button click
        $(document).on('click', '.btn-calendar', function() {
            var userId = $(this).data('id');
            $('#calendarModal').modal('show');
            generateCalendar(userId);
        });

        // Generate calendar with repayment events
        function generateCalendar(userId) {
            var calendar = $('#calendar');
            calendar.empty();

            // Get the current month and year
            var date = new Date();
            var year = date.getFullYear();
            var month = date.getMonth();

            // Get the first day of the month
            var firstDay = new Date(year, month, 1).getDay();

            // Get the number of days in the month
            var daysInMonth = new Date(year, month + 1, 0).getDate();

            // Add empty cells for days before the first day of the month
            for (var i = 0; i < firstDay; i++) {
                calendar.append('<div class="day"></div>');
            }

            // Add cells for each day of the month
            for (var day = 1; day <= daysInMonth; day++) {
                var dayCell = $('<div class="day"></div>');
                dayCell.append('<div class="date">' + day + '</div>');

                // Add events for the day
                repaymentData.forEach(function(event) {
                    var eventDate = new Date(event.repayment_date);
                    if (eventDate.getDate() === day && eventDate.getMonth() === month && eventDate.getFullYear() === year && event.user_id == userId) {
                        var eventText = event.repayment_amount > 0 ? 'Paid: ' + event.repayment_amount : 'Not Paid';
                        dayCell.append('<div class="event">' + eventText + '</div>');
                    }
                });

                calendar.append(dayCell);
            }
        }
    });
    </script>
</body>
</html>