<?php
session_start();
include 'db.php';

// Fetch total paise diye and paise lene from the database
$totalPaiseDiye = 0;
$totalMahineKaByaj = 0;
$totalByajBalance = 0;

$stmt = $conn->prepare("SELECT SUM(paise_diye) as total_paise_diye, SUM(mahine_ka_byaj) as total_mahine_ka_byaj, SUM(remaining_balance) as total_byaj_balance FROM users_details_monthly");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $totalPaiseDiye = $row['total_paise_diye'];
    $totalMahineKaByaj = $row['total_mahine_ka_byaj'];
    $totalByajBalance = $row['total_byaj_balance'];
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'monthly' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: login.php");
    exit();
}
$currentMonthYear = date('Y-m'); // Get the current month and year

// Function to check if today is the 1st of the month
function isFirstDayOfMonth() {
    return date('j') == 1;
}

// Function to calculate full months
function calculateFullMonths($loanDate, $currentDate) {
    $loanDate = new DateTime($loanDate);
    $currentDate = new DateTime($currentDate);
    $fullMonths = ($currentDate->format('Y') - $loanDate->format('Y')) * 12 + ($currentDate->format('m') - $loanDate->format('m'));
    return $fullMonths;
}

// Updated function: Add only the monthly interest to the current balance
function updateBalancesIfNeeded($conn, $currentMonthYear) {
    if (!isFirstDayOfMonth()) {
        return; // No update if today is not the 1st of the month
    }

    $stmt = $conn->prepare("SELECT id, remaining_balance, mahine_ka_byaj, DATE_FORMAT(last_update, '%Y-%m') as last_update_month, date FROM users_details_monthly");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $remaining_balance = $row['remaining_balance'];  // Current balance
        $mahine_ka_byaj = $row['mahine_ka_byaj'];        // Monthly interest
        $lastUpdateMonth = $row['last_update_month'];
        $loanDate = $row['date'];
        $currentDate = date('Y-m-d');                    // Current date as string

        // Check if it's a new month compared to the last update
        if ($currentMonthYear != $lastUpdateMonth) {
            // Add the monthly interest to the current balance
            $remaining_balance += $mahine_ka_byaj;

            // Update the remaining balance and last update month in the database
            $updateStmt = $conn->prepare("UPDATE users_details_monthly SET remaining_balance = ?, last_update = NOW() WHERE id = ?");
            $updateStmt->bind_param("di", $remaining_balance, $id);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    $stmt->close();
}

// Add user details
function addUserDetails($conn, $name, $mobile_number, $paise_diye, $mahine_ka_byaj, $date) {
    $stmt = $conn->prepare("SELECT id FROM users_details_monthly WHERE name = ? AND date = ?");
    $stmt->bind_param("ss", $name, $date);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "User details already exist for the selected date.";
        return false;
    }

    $loanDate = DateTime::createFromFormat('d-m-Y', $date); // Ensure the date is in the correct format
    if (!$loanDate) {
        $_SESSION['error'] = "Invalid date format.";
        return false;
    }
    $currentDate = new DateTime(); // Current date as a DateTime object

    // Calculate the number of full months from the loan date to the current date
    $fullMonths = calculateFullMonths($loanDate->format('Y-m-d'), $currentDate->format('Y-m-d'));

    // Calculate the total interest for these full months
    $totalInterest = $fullMonths * $mahine_ka_byaj;

    $stmt = $conn->prepare("INSERT INTO users_details_monthly (name, mobile_number, paise_diye, mahine_ka_byaj, remaining_balance, date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdds", $name, $mobile_number, $paise_diye, $mahine_ka_byaj, $totalInterest, $loanDate->format('Y-m-d'));
    if ($stmt->execute()) {
        $_SESSION['success'] = "User details added successfully.";
        return true;
    } else {
        $_SESSION['error'] = "Failed to add user details.";
        return false;
    }
}

// Process repayments
function processRepayment($id, $repaymentAmount, $repaymentDate) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'repayment_monthly.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'id' => $id,
        'repaymentAmount' => $repaymentAmount,
        'repaymentDate' => $repaymentDate
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($response, true);
    if ($response['success']) {
        $_SESSION['success'] = "Repayment processed successfully.";
    } else {
        $_SESSION['error'] = $response['message'] ?? "Failed to process repayment.";
    }
}

// Update balances if necessary
updateBalancesIfNeeded($conn, $currentMonthYear);

function updateUserDetails($conn, $id, $name, $mobile_number, $paise_diye, $mahine_ka_byaj, $date) {
    // Fetch the existing data for this ID
    $stmt = $conn->prepare("SELECT * FROM users_details_monthly WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Validate and parse the date
    $loanDate = DateTime::createFromFormat('d-m-Y', $date);
    if (!$loanDate) {
        $_SESSION['error'] = "Invalid date format.";
        return false;
    }

    // Format date for SQL query
    $formattedDate = $loanDate->format('Y-m-d');

    // Calculate the number of full months from the loan date to the current date
    $currentDate = new DateTime();
    $fullMonths = calculateFullMonths($formattedDate, $currentDate->format('Y-m-d'));

    // Calculate the total interest for these full months
    $totalInterest = $fullMonths * $mahine_ka_byaj;

    // Update the record
    $updateStmt = $conn->prepare("UPDATE users_details_monthly SET name = ?, mobile_number = ?, paise_diye = ?, mahine_ka_byaj = ?, remaining_balance = ?, date = ?, last_update = NOW() WHERE id = ?");
    $updateStmt->bind_param("ssddssi", $name, $mobile_number, $paise_diye, $mahine_ka_byaj, $totalInterest, $formattedDate, $id);

    if ($updateStmt->execute()) {
        $_SESSION['success'] = "User details updated successfully.";
        return true;
    } else {
        $_SESSION['error'] = "Failed to update user details.";
        return false;
    }
}

// Process POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['editMode']) && $_POST['editMode'] == '1') {
        $success = updateUserDetails(
            $conn,
            $_POST['userId'],
            $_POST['name'],
            $_POST['mobile_number'],
            $_POST['paise_diye'],
            $_POST['mahine_ka_byaj'],
            $_POST['date']
        );
    } elseif (isset($_POST['name'])) {
        $success = addUserDetails(
            $conn,
            $_POST['name'],
            $_POST['mobile_number'],
            $_POST['paise_diye'],
            $_POST['mahine_ka_byaj'],
            $_POST['date']
        );
    } elseif (isset($_POST['repaymentAmount'])) {
        processRepayment(
            $_POST['id'],
            $_POST['repaymentAmount'],
            $_POST['repaymentDate']
        );
    }
    header("Location: monthly.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Interest Payment</title>
    <link rel="icon" type="image/x-icon" href="assets/img/bank.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" rel="stylesheet">
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/4.5.2/lux/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
    .btn-container {
        display: flex; /* Use flexbox for layout */
        align-items: center; /* Align items vertically centered */
        gap: 5px; /* Space between buttons */
    }
    .btn-icon {
        width: 40px; /* Set width */
        height: 40px; /* Set height */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 50%; /* Optional: makes the button round */
        font-size: 20px; /* Adjust icon size */
        line-height: 1; /* Ensure icon is vertically centered */
    }
    .btn-icon i {
        margin: 0;
    }
</style>

</head>

<body>
    <div class="container" style="max-width: 100%;">

        <div class="daily-main-header pt-4 pb-2">
            <div class="text-center mb-4">
                <button type="button" class="btn btn-primary btn-custom mx-2" data-toggle="modal"
                    data-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
                <button type="button" class="btn btn-success btn-custom mx-2" id="khataButton">
                    <i class="fas fa-book"></i> Khata
                </button>
                <button type="button" class="btn btn-primary btn-custom mx-2"
                    onclick="window.location.href='repaymentpagemonth.php'">
                    <i class="fas fa-money-bill"></i> Repayment
                </button>
                <button type="button" class="btn btn-info btn-custom mx-2" id="totalCollectionButton">
                    <i class="fas fa-coins"></i> Total Collection
                </button>
                <button type="button" class="btn btn-warning btn-custom mx-2" onclick="window.location.href='export_monthly_pdf.php'">
    <i class="fas fa-file-pdf"></i> Export to PDF
</button>
<!-- Button to open the modal -->
<button type="button" class="btn btn-warning btn-custom" id="openMonthModalButton">
    <i class="fas fa-file-pdf"></i> Export Histories
</button>

</div>

                <button type="button" class="btn btn-secondary btn-custom mx-2"
                    onclick="window.location.href='index.php'">
                    <i class="fas fa-arrow-left"></i> Back to Main Page
                </button>
            </div>
        </div>

<!-- Modal for month selection -->
<div class="modal fade" id="monthModal" tabindex="-1" role="dialog" aria-labelledby="monthModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="monthModalLabel">Select Export Option</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="exportOption" id="exportSpecificMonth" value="specific" checked>
                    <label class="form-check-label" for="exportSpecificMonth">
                        Export Specific Month
                    </label>
                </div>
                <div class="form-group" id="monthPickerContainer">
                    <input type="text" id="modalExportMonth" class="form-control monthpicker" placeholder="Select Month">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="exportOption" id="exportAll" value="all">
                    <label class="form-check-label" for="exportAll">
                        Export All Histories
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="exportSelectedMonthButton">Export</button>
            </div>
        </div>
    </div>
</div>
        <!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add User Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" action="monthly.php" id="userForm">
                    <input type="hidden" id="userId" name="userId" value="">
                    <input type="hidden" id="editMode" name="editMode" value="0"> <!-- 0 for add, 1 for edit -->
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
                        <label for="mahine_ka_byaj">Mahine Ka Byaj:</label>
                        <input type="number" id="mahine_ka_byaj" name="mahine_ka_byaj" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="text" id="date" name="date" class="form-control datepicker" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Submit</button>
                </form>
                <?php if (isset($_SESSION['success'])) {
                    echo "<div class='alert alert-success mt-3'>{$_SESSION['success']}</div>";
                    unset($_SESSION['success']);
                } ?>
                <?php if (isset($_SESSION['error'])) {
                    echo "<div class='alert alert-danger mt-3'>{$_SESSION['error']}</div>";
                    unset($_SESSION['error']);
                } ?>
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
                <label for="totalMahineKaByaj">Total Mahine Ka Byaj:</label>
                <span id="totalPaiseDiye"><?php echo $totalMahineKaByaj; ?></span>
            </div>
            <div class="total-collection-item">
                <label for="totalByajBalance">Total Byaj Balance:</label>
                <span id="totalByajBalance"><?php echo $totalByajBalance; ?></span>
            </div>
           
        </div>
    </div>
            <div class="form-group">
                <table id="khataTable" class="display">
                    <thead>
                        <tr>
                            <th>Sr.No.</th>
                            <th>Name</th>
                            <th>Mobile No.</th>
                            <th>Paise Diye</th>
                            <th>Byaj</th>
                            <th>Balance</th>
                            <th>Date</th>
                            <th class="action-width">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
// Initialize a counter for serial numbers
$serialNumber = 1;
// Fetch user details from the database
$result = $conn->query("SELECT * FROM users_details_monthly");
while ($row = $result->fetch_assoc()) {
    // Convert the date to a DateTime object
    $date = new DateTime($row['date']);
    // Format the date as dd-mm-yyyy
    $formattedDate = $date->format('d-m-Y');

    echo "<tr data-id='{$row['id']}' data-mahine_ka_byaj='{$row['mahine_ka_byaj']}'>";
    echo "<td class='serial-number'>{$serialNumber}</td>"; // Display the serial number
    echo "<td class='user-name' data-id='{$row['id']}'>{$row['name']}</td>";
    echo "<td>{$row['mobile_number']}</td>";
    echo "<td>{$row['paise_diye']}</td>";
    echo "<td>{$row['mahine_ka_byaj']}</td>";
    echo "<td>{$row['remaining_balance']}</td>";
    echo "<td>{$formattedDate}</td>"; // Display the formatted date
    echo "<td>";
    if ($row['remaining_balance'] <= 0) {
        echo "<button class='btn btn-success' disabled>Paid</button>";
    } else {
        echo "<button class='btn btn-danger' disabled>Not Paid</button>";
    }
    echo "<button class='btn btn-info btn-history mx-1' data-id='{$row['id']}'>History</button>";
    echo "<button class='btn btn-success btn-edit mx-1' data-id='{$row['id']}' data-name='{$row['name']}' data-mobile_number='{$row['mobile_number']}' data-paise_diye='{$row['paise_diye']}' data-mahine_ka_byaj='{$row['mahine_ka_byaj']}' data-date='{$formattedDate}' title='Edit'>
        <i class='fas fa-edit'></i>
      </button>";
    echo "<button class='btn btn-danger btn-delete mx-1' data-id='{$row['id']}'><i class='fas fa-trash-alt'></i></button>";
    echo "</td>";
    echo "</tr>";
    $serialNumber++;
}
?>
   

                    </tbody>
                </table>
            </div>

            <!-- Repayment Modal -->
            <div class="modal fade" id="repaymentModal" tabindex="-1" role="dialog"
                aria-labelledby="repaymentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="repaymentModalLabel">Repayment</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="repaymentForm">
                                <div class="form-group">
                                    <label for="repaymentAmount">Repayment Amount:</label>
                                    <input type="number" id="repaymentAmount" name="repaymentAmount"
                                        class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="repaymentDate">Repayment Date:</label>
                                    <input type="text" id="repaymentDate" name="repaymentDate"
                                        class="form-control datepicker" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Modal -->
            <div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="historyModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="historyModalLabel">Repayment History</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <button type="button" class="btn btn-warning btn-custom mb-3" id="exportHistoryButton">
                                <i class="fas fa-file-pdf"></i> Export History
                            </button>
                            <table id="historyTable" class="display">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User ID</th>
                                        <th>Repayment Amount</th>
                                        <th>Repayment Date</th>
                                        <th class="action-width">Action</th>
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





            <!-- Total Collection Modal -->
            <div class="modal fade" id="totalCollectionModal" tabindex="-1" role="dialog"
                aria-labelledby="totalCollectionModalLabel" aria-hidden="true">
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
                                    <input type="text" id="collectionDate" name="collectionDate"
                                        class="form-control datepicker">
                                </div>
                                <div class="form-group" id="monthGroup" style="display: none;">
                                    <label for="collectionMonth">Select Month:</label>
                                    <input type="text" id="collectionMonth" name="collectionMonth"
                                        class="form-control monthpicker">
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Submit</button>
                            </form>
                            <div id="collectionResult" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Include jQuery -->
            <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
            <!-- Include jQuery UI -->
            <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
            <!-- Include Bootstrap JS -->
            <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
            <!-- Include DataTables JS -->
            <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
            <!-- Include FullCalendar JS -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
            <script src="assets/js/monthscript.js"></script>
            <script>
$(document).ready(function() {
    $('.btn-edit').on('click', function() {
        var $this = $(this);
        $('#userId').val($this.data('id'));
        $('#name').val($this.data('name'));
        $('#mobile_number').val($this.data('mobile_number'));
        $('#paise_diye').val($this.data('paise_diye'));
        $('#mahine_ka_byaj').val($this.data('mahine_ka_byaj'));
        $('#date').val($this.data('date'));
        $('#editMode').val('1');
        $('#addUserModal').modal('show');
    });

    $('.datepicker').datepicker({
        dateFormat: 'dd-mm-yy',
        autoclose: true
    });

    // Handle history button click to open the modal and set user ID
    $('.btn-history').on('click', function() {
        var userId = $(this).data('id');
        var userName = $('.user-name[data-id="' + userId + '"]').text();
        
        // Set the user ID and name in the history table or modal
        $('#historyTable').data('user-id', userId);
        $('#historyModal').data('user-name', userName);

        // Open the history modal
        $('#historyModal').modal('show');
    });

    // Handle export history button click
    $('#exportHistoryButton').on('click', function() {
        var userId = $('#historyTable').data('user-id');
        var userName = $('#historyModal').data('user-name');
        
        console.log('User ID:', userId);
        console.log('User Name:', userName);

        if (userId && userName) {
            window.location.href = 'export_user_history.php?user_id=' + userId + '&user_name=' + encodeURIComponent(userName);
        } else {
            alert('User ID or Name is missing.');
        }
    });

    // Initialize month picker
    $('.monthpicker').datepicker({
        format: "mm-yyyy",
        startView: "months", 
        minViewMode: "months",
        autoclose: true
    });

    // Handle export all histories button click
    $('#exportAllHistoriesButton').on('click', function() {
        var selectedMonth = $('#exportMonth').val();
        if (!selectedMonth) {
            alert('Please select a month before exporting.');
            return;
        }
        console.log('Selected Month:', selectedMonth); // Debugging line
        var url = 'export_all_user_histories.php?month=' + encodeURIComponent(selectedMonth);
        window.location.href = url;
    });

    // Initialize month picker
    $('#exportMonth').datepicker({
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

    // Handle export all histories button click
    $('#exportAllHistoriesButton').on('click', function() {
        var selectedMonth = $('#exportMonth').val();
        if (!selectedMonth) {
            alert('Please select a month before exporting.');
            return;
        }
        var url = 'export_all_user_histories.php?month=' + encodeURIComponent(selectedMonth);
        window.location.href = url;
    });

    // Initialize month picker in the modal
    $('#modalExportMonth').datepicker({
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

    // Open the modal on button click
    $('#openMonthModalButton').on('click', function() {
        $('#monthModal').modal('show');
    });

    // Handle export button click in the modal
    $('#exportSelectedMonthButton').on('click', function() {
        var exportOption = $('input[name="exportOption"]:checked').val();
        var url = 'export_all_user_histories.php';

        if (exportOption === 'specific') {
            var selectedMonth = $('#modalExportMonth').val();
            if (!selectedMonth) {
                alert('Please select a month before exporting.');
                return;
            }
            url += '?month=' + encodeURIComponent(selectedMonth);
        }

        console.log('Export Option:', exportOption); // Debugging line
        window.location.href = url;
        $('#monthModal').modal('hide');
    });

    // Toggle month picker visibility based on selected option
    $('input[name="exportOption"]').on('change', function() {
        if ($(this).val() === 'specific') {
            $('#monthPickerContainer').show();
        } else {
            $('#monthPickerContainer').hide();
        }
    });
});
</script>


</body>
</html>