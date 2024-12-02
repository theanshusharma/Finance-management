<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $repaymentId = $_POST['id'];

    // Step 1: Fetch the repayment amount and user ID for this repayment record
    $stmt = $conn->prepare("SELECT user_id, repayment_amount FROM repayment_history WHERE id = ?");
    $stmt->bind_param("i", $repaymentId);
    $stmt->execute();
    $stmt->bind_result($userId, $repaymentAmount);
    $stmt->fetch();
    $stmt->close();

    if ($userId && $repaymentAmount) {
        // Step 2: Fetch the current paise_lene for this user
        $stmt = $conn->prepare("SELECT paise_lene FROM users_details WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($paise_lene);
        $stmt->fetch();
        $stmt->close();

        // Step 3: Update the paise_lene by adding back the repayment amount
        $newPaiseLene = $paise_lene + $repaymentAmount;
        $stmt = $conn->prepare("UPDATE users_details SET paise_lene = ? WHERE id = ?");
        $stmt->bind_param("di", $newPaiseLene, $userId);

        if ($stmt->execute()) {
            $stmt->close();

            // Step 4: Delete the repayment record
            $stmt = $conn->prepare("DELETE FROM repayment_history WHERE id = ?");
            $stmt->bind_param("i", $repaymentId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Repayment deleted and balance updated.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete repayment.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user balance.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Repayment not found.']);
    }
}
?>
