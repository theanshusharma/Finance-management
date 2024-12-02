<?php
session_start();
include 'db.php';
// Fetch total paise diye and paise lene from the database
$totalPaiseDiye = 0;
$totalPaiseLene = 0;

$stmt = $conn->prepare("SELECT SUM(paise_diye) as total_paise_diye, SUM(paise_lene) as total_paise_lene FROM users_details");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $totalPaiseDiye = $row['total_paise_diye'];
    $totalPaiseLene = $row['total_paise_lene'];
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'daily' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $mobile_number = $_POST['mobile_number'];
    $paise_diye = $_POST['paise_diye'];
    $paise_lene = $_POST['paise_lene'];
    $date = $_POST['date'];
    $edit_id = isset($_POST['edit_id']) ? $_POST['edit_id'] : null;

    if ($edit_id) {
        // Update existing user
        $stmt = $conn->prepare("UPDATE users_details SET name = ?, mobile_number = ?, paise_diye = ?, paise_lene = ?, date = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $mobile_number, $paise_diye, $paise_lene, $date, $edit_id);
        if ($stmt->execute()) {
            $success = "User details updated successfully.";
        } else {
            $error = "Failed to update user details.";
        }
    } else {
        // Check for duplicate user details
        $stmt = $conn->prepare("SELECT id FROM users_details WHERE name = ? AND date = ?");
        $stmt->bind_param("ss", $name, $date);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "User details already exist for the selected date.";
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users_details (name, mobile_number, paise_diye, paise_lene, date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $mobile_number, $paise_diye, $paise_lene, $date);
            if ($stmt->execute()) {
                $success = "User details added successfully.";
            } else {
                $error = "Failed to add user details.";
            }
        }
    }
}



// Fetch repayment data for all users
$repaymentData = [];
$stmt = $conn->prepare("SELECT user_id, repayment_amount, repayment_date FROM repayment_history");
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
    <title >Daily Repayment</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- Include jQuery UI CSS for Datepicker -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Include Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!-- Include a third-party theme for enhanced look and feel -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/4.5.2/lux/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    
</head>
<body>
    <!-- Main-->
 <div class="container pt-4 daily-main-header" style="max-width: 100%;">
    <div class="row justify-content-center">
        <div class="text-center mb-4">
            <button type="button" class="btn btn-primary btn-custom mx-2 btn-sm" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-user-plus"></i> Add User
            </button>
            <button type="button" class="btn btn-success btn-custom mx-2" id="khataButton">
                <i class="fas fa-book"></i> Khata
            </button>
            <button type="button" class="btn btn-primary btn-custom mx-2" onclick="window.location.href='repaymentpage.php'">
                <i class="fas fa-money-bill"></i> Repayment
            </button>
            <!-- <button type="button" class="btn btn-danger btn-custom mx-2" id="bakayaButton">
                <i class="fas fa-money-bill-wave"></i> Bakaya
            </button> -->
            <button type="button" class="btn btn-info btn-custom mx-2" id="totalCollectionButton">
                <i class="fas fa-coins"></i> Total Collection
            </button>
            <button type="button" class="btn btn-warning btn-custom mx-2" onclick="window.location.href='export_daily_pdf.php'">
    <i class="fas fa-file-pdf"></i> Export to PDF
</button>
<button type="button" class="btn btn-warning btn-custom mx-2" id="openDailyMonthModalButton">
    <i class="fas fa-file-pdf"></i> Export Histories
</button>
            <button type="button" class="btn btn-secondary btn-custom mx-2" onclick="window.location.href='index.php'">
                <i class="fas fa-arrow-left"></i> Back to Main Page
            </button>
        </div>
    </div>
</div>

    <!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add/Edit User Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" action="daily.php">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="mobile_number">Mobile Number:</label>
                        <input type="text" id="mobile_number" name="mobile_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="paise_diye">Paise Diye:</label>
                        <input type="number" id="paise_diye" name="paise_diye" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="paise_lene">Paise Lene:</label>
                        <input type="number" id="paise_lene" name="paise_lene" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="text" id="date" name="date" class="form-control datepicker" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Submit</button>
                </form>
                <?php if (isset($success)) { echo "<div class='alert alert-success mt-3'>$success</div>"; } ?>
                <?php if (isset($error)) { echo "<div class='alert alert-danger mt-3'>$error</div>"; } ?>
            </div>
        </div>
    </div>
</div>


    <!-- Calendar Modal -->
    <div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-labelledby="calendarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calendarModalLabel">Repayment Calendar</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-secondary" id="prevMonth">Previous</button>
                        <h5 id="calendarTitle"></h5>
                        <button class="btn btn-secondary" id="nextMonth">Next</button>
                    </div>
                    <div id="calendar" class="calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Khata Table -->
    <div id="khataSection" style="display: none;">
    <div class="d-flex justify-content-between mb-3">
        <h3 class="text-center">Khata</h3>
        <div class="total-collection-box">
            <div class="total-collection-item">
                <label for="totalPaiseDiye">Total Paise Diye:</label>
                <span id="totalPaiseDiye"><?php echo $totalPaiseDiye; ?></span>
            </div>
            <div class="total-collection-item">
                <label for="totalPaiseLene">Total Paise Lene:</label>
                <span id="totalPaiseLene"><?php echo $totalPaiseLene; ?></span>
            </div>
        </div>
    </div>
    <table id="khataTable" class="display">
        <thead>
            <tr>
                <th>Sr.No.</th>
                <th>Name</th>
                <th>Mobile No.</th>
                <th>Paise Diye</th>
                <th>Paise Lene</th>     
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php

        // Initialize a counter for serial numbers
        $serialNumber = 1;
        // Fetch user details from the database
$result = $conn->query("SELECT * FROM users_details");
while ($row = $result->fetch_assoc()) {
    // Create DateTime object from the date in yyyy-mm-dd format
    $date = new DateTime($row['date']);
    // Reformat the date to dd-mm-yyyy
    $formattedDate = $date->format('d-m-Y');

    echo "<tr data-id='{$row['id']}' data-name='{$row['name']}' data-paise_lene='{$row['paise_lene']}'>";
    echo "<td class='serial-number'>{$serialNumber}</td>"; // Display the serial number
    echo "<td class='user-name' data-id='{$row['id']}'>{$row['name']}</td>";
    echo "<td>{$row['mobile_number']}</td>"; // Display mobile number
    echo "<td>" . intval($row['paise_diye']) . "</td>";
    echo "<td>" . intval($row['paise_lene']) . "</td>";
    echo "<td>{$formattedDate}</td>"; // Display formatted date
    echo "<td>
            <button class='btn btn-info btn-history' data-id='{$row['id']}'><i class='fas fa-history'></i></button>
            <button class='btn btn-warning btn-edit' data-id='{$row['id']}' data-name='{$row['name']}' data-mobile_number='{$row['mobile_number']}' data-paise_diye='{$row['paise_diye']}' data-paise_lene='{$row['paise_lene']}' data-date='{$row['date']}'><i class='fas fa-edit'></i></button>
            <button class='btn btn-secondary btn-calendar' data-id='{$row['id']}'><i class='fas fa-calendar-alt'></i></button>
            <button class='btn btn-danger btn-delete' data-id='{$row['id']}'><i class='fas fa-trash-alt'></i></button>
          </td>";
    echo "</tr>";

    // Increment the counter
    $serialNumber++;
}


        ?>
        </tbody>
    </table>
</div>



        <!-- Bakaya Table -->
<div id="bakayaSection" style="display: none;">
    <h3 class="text-center">Bakaya (Outstanding Amounts)</h3>
    <table id="bakayaTable" class="display">
        <thead>
            <tr>
                <th>Name</th>
                <th>Paise Lene</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <!-- Bakaya data will be populated here -->
        </tbody>
    </table>
</div>

        <!-- History Modal -->
        <div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="historyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="historyModalLabel">Repayment History</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table id="historyTable" class="display">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Repayment Amount</th>
                                    <th>Repayment Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- History data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal for month selection -->
<div class="modal fade" id="dailyMonthModal" tabindex="-1" role="dialog" aria-labelledby="dailyMonthModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dailyMonthModalLabel">Select Export Option</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="dailyExportOption" id="dailyExportSpecificMonth" value="specific" checked>
                    <label class="form-check-label" for="dailyExportSpecificMonth">
                        Export Specific Month
                    </label>
                </div>
                <div class="form-group" id="dailyMonthPickerContainer">
                    <input type="text" id="dailyModalExportMonth" class="form-control monthpicker" placeholder="Select Month">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="dailyExportOption" id="dailyExportAll" value="all">
                    <label class="form-check-label" for="dailyExportAll">
                        Export All Histories
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="dailyExportSelectedMonthButton">Export</button>
            </div>
        </div>
    </div>
</div>
       <!-- Total Collection Modal -->
<div class="modal fade" id="totalCollectionModal" tabindex="-1" role="dialog" aria-labelledby="totalCollectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="totalCollectionModalLabel">Total Collection</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="totalCollectionForm">
                    <div class="form-group">
                        <label for="collectionType">Select View Type:</label>
                        <select id="collectionType" name="collectionType" class="form-control">
                            <option value="date">Date</option>
                            <option value="month">Month</option>
                        </select>
                    </div>
                    <div class="form-group" id="dateGroup">
                        <label for="collectionDate">Select Date:</label>
                        <input type="text" id="collectionDate" name="collectionDate" class="form-control datepicker">
                    </div>
                    <div class="form-group" id="monthGroup" style="display: none;">
                        <label for="collectionMonth">Select Month:</label>
                        <input type="text" id="collectionMonth" name="collectionMonth" class="form-control monthpicker">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Submit</button>
                </form>
                <div id="collectionResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

    <!-- Include jQuery, Bootstrap JS, DataTables JS, and jQuery UI JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/collectionscript.js"></script>
    <script>
$(document).ready(function() {


    // Handle delete button click
    $(document).on('click', '.btn-delete', function() {
        var userId = $(this).data('id');
        var row = $(this).closest('tr');

        if (confirm('Are you sure you want to delete this user?')) {
            $.ajax({
                url: 'delete_user_daily.php',
                type: 'POST',
                data: { id: userId },
                success: function(response) {
                    console.log(response); // Log the response for debugging
                    if (response.trim() == 'success') {
                        row.remove();
                    } else {
                        alert('Failed to delete user: ' + response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error: ' + status + error);
                }
            });
        }
    });
});
</script>
</html>