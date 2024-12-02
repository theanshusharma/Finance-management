<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $repaymentId = $_POST['id'];

    // Step 1: Fetch repayment details (user_id and repayment_amount) for the repayment to be deleted
    $stmt = $conn->prepare("SELECT user_id, repayment_amount FROM repayment_history_monthly WHERE id = ?");
    $stmt->bind_param("i", $repaymentId);
    $stmt->execute();
    $stmt->bind_result($userId, $repaymentAmount);
    $stmt->fetch();
    $stmt->close();

    if ($userId && $repaymentAmount) {
        // Step 2: Fetch the user's current remaining balance
        $stmt = $conn->prepare("SELECT remaining_balance FROM users_details_monthly WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($remaining_balance);
        $stmt->fetch();
        $stmt->close();

        // Step 3: Update remaining balance by adding back the repayment amount
        $newRemainingBalance = $remaining_balance + $repaymentAmount;

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Step 4: Update the user's remaining balance
            $stmt = $conn->prepare("UPDATE users_details_monthly SET remaining_balance = ?, last_update = NOW() WHERE id = ?");
            $stmt->bind_param("di", $newRemainingBalance, $userId);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update remaining balance: ' . $stmt->error);
            }
            $stmt->close();

            // Step 5: Delete the repayment record from repayment_history_monthly
            $stmt = $conn->prepare("DELETE FROM repayment_history_monthly WHERE id = ?");
            $stmt->bind_param("i", $repaymentId);
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete repayment: ' . $stmt->error);
            }
            $stmt->close();

            // Commit the transaction
            $conn->commit();

            echo json_encode(['success' => true, 'message' => 'Repayment deleted and balance updated.']);
        } catch (Exception $e) {
            // Rollback transaction in case of any failure
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Repayment record not found.']);
    }
}
?>
