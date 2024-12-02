<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'monthly' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $repaymentAmount = isset($_POST['repaymentAmount']) ? floatval($_POST['repaymentAmount']) : 0;
    $repaymentDate = isset($_POST['repaymentDate']) ? $_POST['repaymentDate'] : '';

    // Validate input
    if ($id <= 0 || $repaymentAmount <= 0 || empty($repaymentDate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit();
    }

    // Fetch current balance
    $stmt = $conn->prepare("SELECT remaining_balance FROM users_details_monthly WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($remaining_balance);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Process the repayment
    if ($remaining_balance >= $repaymentAmount) {
        $newRemainingBalance = $remaining_balance - $repaymentAmount;

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Update remaining balance
            $stmt = $conn->prepare("UPDATE users_details_monthly SET remaining_balance = ?, last_update = NOW() WHERE id = ?");
            $stmt->bind_param("di", $newRemainingBalance, $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update balance: ' . $stmt->error);
            }
            $stmt->close();

            // Record repayment
            $stmt = $conn->prepare("INSERT INTO repayment_history_monthly (user_id, repayment_amount, repayment_date) VALUES (?, ?, ?)");
            $stmt->bind_param("ids", $id, $repaymentAmount, $repaymentDate);
            if (!$stmt->execute()) {
                throw new Exception('Failed to record repayment: ' . $stmt->error);
            }
            $stmt->close();

            // Commit transaction
            $conn->commit();

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Repayment amount exceeds remaining balance.']);
    }
}
?>
