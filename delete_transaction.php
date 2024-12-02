<?php
include 'db.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the transaction ID from the POST request
    $transactionId = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Validate the transaction ID
    if ($transactionId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID.']);
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Fetch the transaction details
        $stmt = $conn->prepare("SELECT user_id, transaction_amount, previous_balance FROM transaction_history WHERE id = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $stmt->bind_result($userId, $transactionAmount, $previousBalance);

        if (!$stmt->fetch()) {
            throw new Exception('Transaction not found.');
        }
        $stmt->close();

        // Fetch the current wallet balance
        $stmt = $conn->prepare("SELECT wallet FROM users_bet WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($currentWalletBalance);

        if (!$stmt->fetch()) {
            throw new Exception('User not found.');
        }
        $stmt->close();

        // Revert the wallet balance
        $newWalletBalance = $previousBalance;

        // Update wallet balance
        $stmt = $conn->prepare("UPDATE users_bet SET wallet = ? WHERE id = ?");
        $stmt->bind_param("di", $newWalletBalance, $userId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update wallet: ' . $stmt->error);
        }
        $stmt->close();

        // Delete the transaction record
        $stmt = $conn->prepare("DELETE FROM transaction_history WHERE id = ?");
        $stmt->bind_param("i", $transactionId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete transaction: ' . $stmt->error);
        }
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Transaction successfully deleted.']);
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // If the request method is not POST
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
