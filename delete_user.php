<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['id'];

    // Enable error reporting
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Attempt to delete related records from repayment_history
        $stmt = $conn->prepare("DELETE FROM repayment_history_monthly WHERE user_id = ?");
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Attempt to delete the user from users_details
        $stmt = $conn->prepare("DELETE FROM users_details_monthly WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();
        echo 'success';
    } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollback();
        echo 'error: ' . $e->getMessage();
    }

    $conn->close();
}
?>
