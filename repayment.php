<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $repaymentAmount = $_POST['repaymentAmount'];
    $repaymentDate = $_POST['repaymentDate'];

    // Fetch the current paise_lene
    $stmt = $conn->prepare("SELECT paise_lene FROM users_details WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($paise_lene);
    $stmt->fetch();
    $stmt->close();

    if ($paise_lene >= $repaymentAmount) {
        // Update the paise_lene
        $newPaiseLene = $paise_lene - $repaymentAmount;
        $stmt = $conn->prepare("UPDATE users_details SET paise_lene = ? WHERE id = ?");
        $stmt->bind_param("di", $newPaiseLene, $id);
        if ($stmt->execute()) {
            // Insert into repayment history
            $stmt = $conn->prepare("INSERT INTO repayment_history (user_id, repayment_amount, repayment_date) VALUES (?, ?, ?)");
            $stmt->bind_param("ids", $id, $repaymentAmount, $repaymentDate);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } else {
        echo json_encode(['success' => false]);
    }
}
?>