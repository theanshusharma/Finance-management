<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'daily' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

// Fetch user names from the database
$users = [];
$result = $conn->query("SELECT id, name FROM users_details");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Fetch repayment data based on selected date
$repayments = [];
$totalRepayment = 0;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : '';

if ($selectedDate) {
    // Fetch repayments for the selected date
    $stmt = $conn->prepare("SELECT r.id, r.user_id, r.repayment_amount, r.repayment_date, u.name FROM repayment_history r JOIN users_details u ON r.user_id = u.id WHERE r.repayment_date = ?");
    $stmt->bind_param("s", $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $repayments[] = $row;
        $totalRepayment += $row['repayment_amount'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Repayment Page</title>
    <link rel="icon" type="image/x-icon" href="assets/img/bank.png">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
.table-container {
    max-height: 300px; /* Adjust the height as needed */
    overflow-y: auto;  /* Enable vertical scrolling */
    overflow-x: hidden; /* Hide horizontal scrolling if not needed */
}
</style>

</head>

<body>
 <!-- Header -->
 <header class="text-white text-center  repayment-header" style="background-color: black;">
        <h2 class="text-white text-center py-2" >Daily Finance</h2>
    </header>
    <div class="container pt-4">
        <div>
            <div class="text-center mb-3">
                <button type="button" class="btn btn-info btn-custom mx-2" onclick="window.location.href='daily.php'">
                    <i class="fas fa-arrow-left"></i> Back to Daily
                </button>
                <button type="button" class="btn btn-info btn-custom mx-2" onclick="window.location.href='index.php'">
                    <i class="fas fa-arrow-left"></i> Back to Main Page
                </button>
            </div>
            <form id="repaymentForm" method="post" action="repayment.php">
                <input type="hidden" id="repaymentDate" name="repaymentDate"
                    value="<?php echo htmlspecialchars($selectedDate); ?>">
                <div class="form-container">
                    <div class="form-group">
                        <label for="userName">User Name:</label>
                        <select id="userName" name="id" class="form-control" required>
                            <?php foreach ($users as $user) { ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo $user['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="datePicker">Select Date:</label>
                        <input type="text" id="datePicker" class="form-control datepicker"
                            value="<?php echo htmlspecialchars($selectedDate); ?>" required>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <div class="repayment-patch">
                        <div class="repayment-amount-patch">
                            <label for="repaymentAmount">Repayment Amount:</label>
                            <input type="number" id="repaymentAmount" name="repaymentAmount" class="form-control"
                                required>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-success btn-lg">Save</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>


        <!-- Display Repayment Data -->
<div class="d-flex repayment-collection">
    <?php if ($selectedDate) { ?>
        <div class="total-repayment">
            <h3>Total Repayment for <?php echo htmlspecialchars($selectedDate); ?>:</h3>
            <h3><?php echo intval($totalRepayment); ?></h3>

        </div>
    <?php } ?>

    <!-- Display Repayment Data -->
    <div class="repayment-list">
        <?php if (!empty($repayments)) { ?>
            <h4 class="text-center">Repayments for <?php echo htmlspecialchars($selectedDate); ?></h4>
            <div class="table-container">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>User Name</th>
                            <th>Repayment Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $serialNo = 1; // Initialize serial number
                        foreach ($repayments as $repayment) { ?>
                            <tr>
                                <td><?php echo $serialNo++; ?></td> <!-- Display serial number -->
                                <td><?php echo htmlspecialchars($repayment['name']); ?></td>
                                <td><?php echo intval($repayment['repayment_amount']); ?></td>
                                <td>
                                    <button class="btn btn-danger delete-btn"
                                        data-id="<?php echo $repayment['id']; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <p class="text-center">No repayments found for the selected date.</p>
        <?php } ?>
    </div>
</div>

    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(function () {
            $(".datepicker").datepicker({ dateFormat: "yy-mm-dd" });

            // Update the URL when a date is selected
            $('#datePicker').on('change', function () {
                const selectedDate = $(this).val();
                window.location.href = `repaymentpage.php?date=${selectedDate}`;
            });
            // Handle form submission
            $('#repaymentForm').on('submit', function (event) {
                event.preventDefault();
                alert('Do you want to save transaction.');

                $.ajax({
                    url: 'repayment.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            

                            // Reload the repayments table to reflect new data
                            window.location.reload();
                        } else {
                            alert('Failed to add repayment. Please check your inputs.');
                        }
                    },
                    error: function () {
                        alert('An error occurred while processing your request.');
                    }
                });
            });
             // Prevent form submission on Enter key
    $('#repaymentForm').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent form submission
        }
    });
            // Handle deletion of repayment records
            $('.delete-btn').on('click', function () {
                const repaymentId = $(this).data('id');
                {
                    $.ajax({
                        url: 'delete_repayment.php',
                        type: 'POST',
                        data: { id: repaymentId },
                        success: function (response) {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert('Record Delete Sucessfully');

                                // Reload the repayments table to reflect changes
                                window.location.reload();
                            } else {
                                alert('Failed to delete repayment.');
                            }
                        },
                        error: function () {
                            alert('An error occurred while processing your request.');
                        }
                    });
                }
            });

            // Set date picker value based on URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const dateParam = urlParams.get('date');
            if (dateParam) {
                $('#datePicker').val(dateParam);
            }
        });

    </script>
</body>

</html>