<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'bet' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

// Add/Edit user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_user') {
        $name = $_POST['name'];
        $wallet = $_POST['wallet'];
        $mobile_number = $_POST['mobile_number'];

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name cannot be empty.']);
            exit();
        }

        // Check for existing user
        $stmt = $conn->prepare("SELECT id FROM users_bet WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'User already exists.']);
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users_bet (name, wallet, mobile_number) VALUES (?, ?, ?)");
            $stmt->bind_param("sds", $name, $wallet, $mobile_number); // Bind mobile number
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User added successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add user.']);
            }
        }
        $stmt->close();
    } elseif ($_POST['action'] == 'edit_user') {
        $id = intval($_POST['id']);
        $name = $_POST['name'];
        $wallet = $_POST['wallet'];
        $mobile_number = $_POST['mobile_number'];

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name cannot be empty.']);
            exit();
        }

        // Update user
        $stmt = $conn->prepare("UPDATE users_bet SET name = ?, wallet = ?, mobile_number = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $name, $wallet, $mobile_number, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user.']);
        }
        $stmt->close();
    } elseif ($_POST['action'] == 'delete_user') {
        $id = intval($_POST['id']);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM users_bet WHERE id = ?");
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $stmt->error]);
        }
        $stmt->close();
    }
    exit();
}

// Fetch users and calculate totals
$users = [];
$total_paise_lene = 0;
$total_paise_dene = 0;

$stmt = $conn->prepare("SELECT * FROM users_bet");
$stmt->execute();
$result = $stmt->get_result();
$serial_number = 1; // Start serial number from 1

while ($row = $result->fetch_assoc()) {
    $row['serial_number'] = $serial_number++;
    $row['paise_lene'] = $row['wallet'] < 0 ? abs($row['wallet']) : 0;
    $row['paise_dene'] = $row['wallet'] > 0 ? $row['wallet'] : 0;
    
    $total_paise_lene += $row['paise_lene'];
    $total_paise_dene += $row['paise_dene'];
    
    $users[] = $row;
}

$stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link rel="icon" type="image/x-icon" href="assets/img/bank.png">
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- jQuery UI CSS and JS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
<header class="text-white text-center py-3 repayment-header" style="background-color: black;">
        <h1 class="text-white text-center py-3" >Bet Record</h1>
    </header>
<div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
            <i class="fas fa-user-plus"></i> Add User
        </button>
        <a href="transaction_manager.php" class="btn btn-secondary">
            <i class="fas fa-calendar-day"></i> Daily Repayment
        </a>
        <button class="btn btn-info" data-toggle="modal" data-target="#totalsModal">
            <i class="fas fa-calculator"></i> Check Totals
        </button>
        <button type="button" class="btn btn-warning" onclick="window.location.href='export_user_management_pdf.php'">
            <i class="fas fa-file-pdf"></i> Export User Details
        </button>
        <button class="btn btn-info" onclick="window.location.href='index.php'">
            <i class="fas fa-arrow-left"></i> Back to Main Page
        </button>
    </div>
</div>

<!-- Totals Box -->
<div class="totals-box">
    <div class="total-item">
        <h5>Total Paise Lene:</h5>
        <p id="totalPaiseLene"><?= $total_paise_lene ?></p>
    </div>
    <div class="total-item">
        <h5>Total Paise Dene:</h5>
        <p id="totalPaiseDene"><?= $total_paise_dene ?></p>
    </div>
</div>
    <table id="userTable" class="display table table-striped table-bordered">
        <thead>
            <tr>
                <th>Sr. No.</th>
                <th>Name</th>
                <th>Mobile Number</th>
                <th>Wallet</th>
                <th>Paise Lene</th>
                <th>Paise Dene</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr data-id="<?= $user['id'] ?>">
                    <td><?= $user['serial_number'] ?></td>
                    <td><?= $user['name'] ?></td>
                    <td><?= $user['mobile_number'] ?></td>
                    <td><?= intval($user['wallet'], 0) ?></td>
<td><?= intval($user['paise_lene'], 0) ?></td>
<td><?= intval($user['paise_dene'], 0) ?></td>

                    <td>
                        <button class="btn btn-info btn-history" data-id="<?= $user['id'] ?>">
                            <i class="fas fa-history"></i> History
                        </button>
                        <button class="btn btn-warning btn-edit" data-id="<?= $user['id'] ?>" data-name="<?= $user['name'] ?>" data-wallet="<?= $user['wallet'] ?>" data-mobile="<?= $user['mobile_number'] ?>">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-delete" data-id="<?= $user['id'] ?>">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add/Edit User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId" name="userId">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="mobile_number">Mobile Number:</label>
                        <input type="text" id="mobile_number" name="mobile_number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="wallet">Wallet:</label>
                        <input type="number" id="wallet" name="wallet" class="form-control" step="0.01" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitButton">Add User</button>
                </form>
                <div id="formMessage" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

 <!-- Totals Modal -->
 <div class="modal fade" id="totalsModal" tabindex="-1" role="dialog" aria-labelledby="totalsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="totalsModalLabel">Totals</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="selectType">Select Type:</label>
                            <div>
                                <input type="radio" id="selectDateType" name="selectType" value="date"> Date
                                <input type="radio" id="selectMonthType" name="selectType" value="month"> Month
                            </div>
                        </div>
                        <div class="form-group" id="datePickerContainer" style="display: none;">
                            <label for="selectDate">Select Date:</label>
                            <input type="text" id="selectDate" class="form-control datepicker">
                        </div>
                        <div class="form-group" id="monthPickerContainer" style="display: none;">
                            <label for="selectMonth">Select Month:</label>
                            <input type="text" id="selectMonth" class="form-control monthpicker">
                        </div>
                        <button type="button" class="btn btn-primary" id="fetchTotals">Fetch Totals</button>
                        <div id="totalsResult" class="mt-3">
                            <p>Total Transaction Amount: <span id="totalTransactionAmount">0.00</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalLabel">User History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table id="historyTable" class="display table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Transaction Amount</th>
                            <th>Transaction Date</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="downloadHistory">
                    <i class="fas fa-file-pdf"></i> Download History
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Date Selection Modal -->
<div class="modal fade" id="dateSelectionModal" tabindex="-1" role="dialog" aria-labelledby="dateSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dateSelectionModalLabel">Select Date Range</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="startDate">Start Date:</label>
                    <input type="text" id="startDate" class="form-control datepicker">
                </div>
                <div class="form-group">
                    <label for="endDate">End Date:</label>
                    <input type="text" id="endDate" class="form-control datepicker">
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="downloadAllHistory">
                    <label class="form-check-label" for="downloadAllHistory">Download All History</label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="confirmDownload">
                    <i class="fas fa-file-pdf"></i> Download
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize date pickers
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        todayHighlight: true,
        autoclose: true
    });

    // History button click
    $(document).on('click', '.btn-history', function() {
        var userId = $(this).data('id');
        var userName = $(this).closest('tr').find('td:nth-child(2)').text(); // Get the user's name from the table row

        $.ajax({
            url: 'fetch_user_history.php',
            type: 'GET',
            data: { id: userId },
            success: function(response) {
                var transactions = JSON.parse(response);
                $('#historyTable').DataTable({
                    paging: false,
                    data: transactions.data,
                    destroy: true,
                    columns: [
                        { data: 'transaction_amount', render: $.fn.dataTable.render.number(',', '.', 2, '') },
                        { data: 'transaction_date' }
                    ],
                    language: {
                        emptyTable: 'No transactions found.'
                    }
                });

                // Store user ID and name for download
                $('#downloadHistory').data('id', userId).data('name', userName);

                $('#historyModal').modal('show');
            }
        });
    });

    // Open date selection modal
    $(document).on('click', '#downloadHistory', function() {
        $('#dateSelectionModal').modal('show');
    });

    // Confirm download button click
    $(document).on('click', '#confirmDownload', function() {
        var userId = $('#downloadHistory').data('id');
        var userName = $('#downloadHistory').data('name');
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var downloadAll = $('#downloadAllHistory').is(':checked');

        var url = `export_user_history.php?user_id=${userId}&user_name=${encodeURIComponent(userName)}`;

        if (downloadAll) {
            // Download all history
            window.location.href = url;
        } else if (startDate && !endDate) {
            // Download specific date
            url += `&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(startDate)}`;
            window.location.href = url;
        } else if (startDate && endDate) {
            // Download date range
            url += `&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
            window.location.href = url;
        } else {
            alert('Please select a specific date, a date range, or choose to download all history.');
        }

        $('#dateSelectionModal').modal('hide');
    });

    // Initialize the month picker for month selection
    $('.monthpicker').datepicker({
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        dateFormat: 'yy-mm',
        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).val(year + '-' + (parseInt(month) + 1).toString().padStart(2, '0'));
        }
    }).focus(function() {
        $(".ui-datepicker-calendar").hide();
        $("#ui-datepicker-div").position({
            my: "center top",
            at: "center bottom",
            of: $(this)
        });
    });

    // Show/hide pickers based on radio button selection
    $('input[name="selectType"]').on('change', function() {
        if ($(this).val() === 'date') {
            $('#datePickerContainer').show();
            $('#monthPickerContainer').hide();
            $('#selectMonth').val(''); // Clear month picker value
        } else if ($(this).val() === 'month') {
            $('#datePickerContainer').hide();
            $('#monthPickerContainer').show();
            $('#selectDate').val(''); // Clear date picker value
        }
    });

    $('#fetchTotals').on('click', function() {
        var date = $('#selectDate').val();
        var month = $('#selectMonth').val();

        if (!date && !month) {
            alert('Please select either a date or a month.');
            return;
        }

        // Convert date format from MM/DD/YYYY to YYYY-MM-DD
        if (date) {
            var dateParts = date.split('/');
            date = dateParts[2] + '-' + dateParts[0] + '-' + dateParts[1];
        }

        console.log('Date:', date);
        console.log('Month:', month);

        $.ajax({
            url: 'fetch_totals.php',
            type: 'POST',
            data: {
                date: date,
                month: month
            },
            success: function(response) {
                console.log('Response:', response);
                response = JSON.parse(response);
                if (response.success) {
                    var totalAmount = parseFloat(response.total_amount);
                    if (!isNaN(totalAmount)) {
                        $('#totalTransactionAmount').text(totalAmount.toFixed(2));
                    } else {
                        $('#totalTransactionAmount').text('0.00');
                    }
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Initialize DataTable
    $('#userTable').DataTable({
        paging: false,
        searching: true,
        info: false
    });

    // Calculate and update total paise lene and paise dene
    function updateTotals() {
        var totalPaiseLene = 0;
        var totalPaiseDene = 0;

        $("#userTable tbody tr").each(function() {
            var paiseLene = parseInt($(this).find("td:nth-child(5)").text());
            var paiseDene = parseInt($(this).find("td:nth-child(6)").text());

            totalPaiseLene += paiseLene;
            totalPaiseDene += paiseDene;
        });

        $("#totalPaiseLene").text(totalPaiseLene);
        $("#totalPaiseDene").text(totalPaiseDene);
    }

    // Call updateTotals function when the document is ready
    updateTotals();

    // Add/Edit user form submission
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        let action = $('#userId').val() ? 'edit_user' : 'add_user';
        $.ajax({
            url: 'user_management.php',
            type: 'POST',
            data: {
                action: action,
                id: $('#userId').val(),
                name: $('#name').val(),
                wallet: $('#wallet').val(),
                mobile_number: $('#mobile_number').val()
            },
            success: function(response) {
                response = JSON.parse(response);
                $('#formMessage').text(response.message);
                if (response.success) {
                    location.reload();
                }
            }
        });
        updateTotals();
    });

    // Edit button click
    $(document).on('click', '.btn-edit', function() {
        $('#userId').val($(this).data('id'));
        $('#name').val($(this).data('name'));
        $('#wallet').val($(this).data('wallet'));
        $('#mobile_number').val($(this).data('mobile'));
        $('#addUserModalLabel').text('Edit User');
        $('#submitButton').text('Update User');
        $('#addUserModal').modal('show');
    });

    // Delete user button click
    $(document).on('click', '.btn-delete', function() {
        var userId = $(this).data('id');
        if (confirm('Are you sure you want to delete this user?')) {
            $.ajax({
                url: 'user_management.php',
                type: 'POST',
                data: {
                    action: 'delete_user',
                    id: userId
                },
                success: function(response) {
                    response = JSON.parse(response);
                    alert(response.message);
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
        updateTotals();
    });
});
</script>
</body>
</html>
