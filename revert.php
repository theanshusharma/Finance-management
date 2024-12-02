<?php
session_start();
include 'db.php';

// Function to check if today is the 1st of the month
function isFirstDayOfMonth() {
    return date('j') == 1;
}

// Function to revert the balance update that happened today
function revertTodaysBalanceUpdate($conn) {
    if (!isFirstDayOfMonth()) {
        echo "Today is not the 1st of the month. No balance update to revert.";
        return; // No need to revert if today is not the 1st of the month
    }

    // Get all users whose balance was updated today
    $stmt = $conn->prepare("SELECT id, remaining_balance, mahine_ka_byaj, DATE_FORMAT(last_update, '%Y-%m-%d') as last_update_date FROM users_details_monthly WHERE last_update = CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $remaining_balance = $row['remaining_balance'];
            $mahine_ka_byaj = $row['mahine_ka_byaj'];

            // Subtract the monthly interest that was added today
            $remaining_balance -= $mahine_ka_byaj;

            // Update the remaining balance in the database
            $updateStmt = $conn->prepare("UPDATE users_details_monthly SET remaining_balance = ?, last_update = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id = ?");
            $updateStmt->bind_param("di", $remaining_balance, $id);
            $updateStmt->execute();
            $updateStmt->close();

            echo "Balance reverted for user ID: $id. New balance: $remaining_balance\n";
        }
    } else {
        echo "No balances were updated today.";
    }

    $stmt->close();
}

// Main logic: Revert the balance update that happened today
revertTodaysBalanceUpdate($conn);

?>
