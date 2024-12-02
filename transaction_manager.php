<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'bet' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

// Fetch users from the database for the dropdown
$users = [];
$result = $conn->query("SELECT id, name FROM users_bet");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Initialize variables for transactions
$transactions = [];
$totalTransaction = 0.00;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : ($users[0]['id'] ?? 0);

// Fetch selected user's name
$stmt = $conn->prepare("SELECT name FROM users_bet WHERE id = ?");
$stmt->bind_param("i", $selectedUserId);
$stmt->execute();
$stmt->bind_result($selectedUserName);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $transactionAmount = isset($_POST['transactionAmount']) ? floatval($_POST['transactionAmount']) : 0;
    $transactionDate = isset($_POST['transactionDate']) ? $_POST['transactionDate'] : '';

    // Validate input
    if (empty($userId) || $transactionDate === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit();
    }

    // Fetch user details
    $stmt = $conn->prepare("SELECT name, wallet FROM users_bet WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($userName, $wallet);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Calculate new wallet balance
    $previousBalance = $wallet;
    $newWallet = $wallet + $transactionAmount;

    // Begin transaction
    $conn->begin_transaction();
    try {
        // Update wallet balance
        $stmt = $conn->prepare("UPDATE users_bet SET wallet = ? WHERE id = ?");
        $stmt->bind_param("di", $newWallet, $userId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update wallet: ' . $stmt->error);
        }
        $stmt->close();

        // Record transaction
        $stmt = $conn->prepare("INSERT INTO transaction_history (user_id, transaction_amount, transaction_date, previous_balance, updated_wallet_balance) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("idsdd", $userId, $transactionAmount, $transactionDate, $previousBalance, $newWallet);
        if (!$stmt->execute()) {
            throw new Exception('Failed to record transaction: ' . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction successfully recorded.']);
        exit();
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Fetch total transactions and transaction data for selected user and date
$stmt = $conn->prepare("
    SELECT th.id, th.transaction_amount, th.transaction_date, th.previous_balance, th.updated_wallet_balance
    FROM transaction_history th
    WHERE th.user_id = ? AND DATE(th.transaction_date) = ?");
$stmt->bind_param("is", $selectedUserId, $selectedDate);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();

// Fetch all transactions for the selected date (all users)
$totalTransactions = [];
$stmt = $conn->prepare("
    SELECT th.id, u.name, th.transaction_amount, th.previous_balance, th.updated_wallet_balance, th.transaction_date
    FROM transaction_history th
    JOIN users_bet u ON th.user_id = u.id
    WHERE DATE(th.transaction_date) = ?");
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $totalTransactions[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Manager</title>
    <link rel="icon" type="image/x-icon" href="assets/img/bank.png">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .table-container {
            margin-bottom: 30px;
            max-height: 400px;
            overflow-y: auto;
        }
        .table-container table {
            width: 100%;
            table-layout: fixed;
        }
        .table-container th, .table-container td {
            word-wrap: break-word;
        }
    </style>
</head>
<body>
<header class="text-white text-center repayment-header" style="background-color: black;">
    <h2 class="text-white text-center py-2">Daily Bet</h2>
</header>
<div class="container pt-4">
    <div class="text-center mb-4">
        <button type="button" class="btn btn-info btn-custom mx-2" onclick="window.location.href='index.php'">
            <i class="fas fa-arrow-left"></i> Back to Main Page
        </button>
        <button type="button" class="btn btn-info btn-custom mx-2" onclick="window.location.href='user_management.php'">
            <i class="fas fa-arrow-left"></i> Back to Account
        </button>
    </div>

    <!-- User Selection and Date Picker on the Same Line -->
    <form id="transactionForm">
        <div class="form-row form-row-custom">
            <div class="col-md-6 form-group form-group-custom">
                <label for="userName">Select User:</label>
                <select id="userName" name="id" class="form-control" required>
                    <?php foreach ($users as $user) { ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $selectedUserId) ? 'selected' : ''; ?>>
                            <?php echo $user['name']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-6 form-group form-group-custom">
                <label for="datePicker">Select Date:</label>
                <input type="text" id="datePicker" class="form-control datepicker" value="<?php echo htmlspecialchars($selectedDate); ?>" required>
                <input type="hidden" id="transactionDate" name="transactionDate" value="<?php echo htmlspecialchars($selectedDate); ?>">
            </div>
        </div>

       
<!-- Transaction Amount and Submit Button on the Same Line -->
<div class="form-row form-row-custom">
<?php
// Retain amountType after reload
$selectedAmountType = isset($_GET['amountType']) ? $_GET['amountType'] : 'positive'; // Default is 'positive'
?>

<div class="col-md-2 form-group form-group-custom">
    <label for="amountType">Type:</label>
    <select id="amountType" name="amountType" class="form-control" required>
        <option value="positive" <?php echo ($selectedAmountType == 'positive') ? 'selected' : ''; ?>>+</option>
        <option value="negative" <?php echo ($selectedAmountType == 'negative') ? 'selected' : ''; ?>>-</option>
    </select>
</div>


    <div class="col-md-6 form-group form-group-custom">
        <label for="transactionAmount">Transaction Amount:</label>
        <input type="number" id="transactionAmount" name="transactionAmount" class="form-control" step="0.01" required>
    </div>

    <div class="col-md-4 form-group form-group-custom d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Save</button>
    </div>
</div>
<!-- Total-->
<div class="container mt-4">
    <h4 class="text-center">Total Transaction Amount</h4>
    <table class="table table-bordered bg-success">
        <thead>
            <tr>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td id="totalTransactionAmount">0.00</td>
            </tr>
        </tbody>
    </table>
</div>




    <!-- Transactions and Total Transactions Side by Side -->
    <div class="row mt-5">
        <!-- Transactions for Selected User -->
        <div class="col-md-6">
            <div class="table-container">
                <?php if (!empty($transactions)) { ?>
                    <h4 class="text-center">Transactions for User: <?php echo htmlspecialchars($selectedUserName); ?></h4>
                    <div class="table-responsive">
                        <table class="table table-bordered bg-white daily-bet-repayment">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Last bal</th>
                                    <th>Amount</th>
                                    <th>New bal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="transactionTableBody">
                                <?php $srNo = 1; foreach ($transactions as $transaction) { ?>
                                    <tr>
                                        <td><?php echo $srNo++; ?></td>
                                        <td><?php echo intval($transaction['previous_balance']); ?></td>
                                        <td><?php echo intval($transaction['transaction_amount']); ?></td>
                                        <td><?php echo intval($transaction['updated_wallet_balance']); ?></td>
                                        <td>
                                            <button class="btn btn-danger delete-btn" data-id="<?php echo $transaction['id']; ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <p>No transactions found for this user on <?php echo htmlspecialchars($selectedDate); ?>.</p>
                <?php } ?>
            </div>
        </div>

        <!-- All Transactions for Selected Date -->
        <div class="col-md-6">
            <div class="table-container">
                <?php if (!empty($totalTransactions)) { ?>
                    <h4 class="text-center">Total Transactions for Date: <?php echo htmlspecialchars($selectedDate); ?></h4>
                    <div class="table-responsive">
                        <table class="table table-bordered bg-white daily-bet-repayment">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Name</th>
                                    <th>Last bal</th>
                                    <th>Amount</th>
                                    <th>New bal</th>
                                </tr>
                            </thead>
                            <tbody id="totalTransactionTableBody">
                                <?php $srNo = 1; foreach ($totalTransactions as $transaction) { ?>
                                    <tr>
                                        <td><?php echo $srNo++; ?></td>
                                        <td><?php echo htmlspecialchars($transaction['name']); ?></td>
                                        <td><?php echo intval($transaction['previous_balance']); ?></td>
                                        <td><?php echo intval($transaction['transaction_amount']); ?></td>
                                        <td><?php echo intval($transaction['updated_wallet_balance']); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <p>No transactions found for the selected date.</p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="assets/js/scripts.js"></script>
<script>
    $(document).ready(function () {
        // Initialize datepicker
        $('#datePicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        }).on('changeDate', function (e) {
            window.location.href = '?date=' + e.format('yyyy-mm-dd') + '&user_id=' + $('#userName').val();
        });
        $('#transactionForm').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent form submission
        }
         });
         // Store the selected amount type in a variable
        var selectedAmountType = '<?php echo $selectedAmountType; ?>';

        // Handle user selection change
        $('#userName').on('change', function () {
            const selectedUserId = $(this).val();
            const selectedDate = $('#datePicker').val(); // Get currently selected date
            window.location.href = `transaction_manager.php?date=${selectedDate}&user_id=${selectedUserId}&amountType=${selectedAmountType}`;
        });
        $('#transactionForm').submit(function (e) {
    e.preventDefault(); // Prevent default form submission behavior

    // Fetch the transaction amount and amount type
    var transactionAmount = parseFloat($('#transactionAmount').val());
    var amountType = $('#amountType').val(); // Get the selected amount type (+ or -)

    // Adjust the amount based on the selected type
    if (amountType === 'negative') {
        transactionAmount = -Math.abs(transactionAmount); // Ensure it remains negative
    } else {
        transactionAmount = Math.abs(transactionAmount); // Ensure it's positive
    }

    // Confirmation before saving
    if (confirm('Are you sure you want to save this transaction?')) {
        // Prepare form data for submission
        var formData = {
            id: $('#userName').val(),
            transactionAmount: transactionAmount,
            transactionDate: $('#transactionDate').val(),
            amountType: amountType // Send the selected amount type
        };

        // Send the form data via AJAX
        $.ajax({
            url: '',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                
                if (response.success) {
                    // Reload the page with the correct amountType in the URL
                    window.location.href = '?date=' + formData.transactionDate + '&user_id=' + formData.id + '&amountType=' + formData.amountType;
                }
            }
        });
    } else {
        // If the user clicks "Cancel", stop the form submission
        alert('Transaction canceled.');
    }
});


        // Handle delete button click
        $('.delete-btn').click(function () {
            if (confirm('Are you sure you want to delete this transaction?')) {
                var transactionId = $(this).data('id');
                $.ajax({
                    url: 'delete_transaction.php',
                    method: 'POST',
                    data: { id: transactionId },
                    success: function (response) {
                        window.location.reload();
                    }
                });
            }
        });

        // Calculate and display total transaction amount
        function calculateTotalTransactionAmount() {
            var totalAmount = 0;
            $('#totalTransactionTableBody tr').each(function () {
                var amount = parseFloat($(this).find('td:nth-child(4)').text());
                totalAmount += amount;
            });
            $('#totalTransactionAmount').text(totalAmount.toFixed(2));
        }

        // Call the function to calculate total transaction amount
        calculateTotalTransactionAmount();
    });
</script>
</body>
</html>
