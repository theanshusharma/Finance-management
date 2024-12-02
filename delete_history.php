<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];

    // Enable error reporting
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Database connection
    require 'db.php'; // Ensure you have the correct path to your database connection file

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Fetch the repayment amount before deleting
        $stmt = $conn->prepare("SELECT user_id, repayment_amount FROM repayment_history WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        $stmt->bind_result($userId, $repaymentAmount);
        $stmt->fetch();
        $stmt->close();

        // Delete the repayment record
        $stmt = $conn->prepare("DELETE FROM repayment_history WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        $stmt->close();

        // Update the paise_lene amount
        $stmt = $conn->prepare("UPDATE users_details SET paise_lene = paise_lene + ? WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("di", $repaymentAmount, $userId);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        $stmt->close();

        // Commit the transaction
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $conn->close();
}
?>