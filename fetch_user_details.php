<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['id'];

    $stmt = $conn->prepare("SELECT date, paid_amount, remaining_amount FROM user_payments WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }

    if (count($details) > 0) {
        echo json_encode(['success' => true, 'details' => $details]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>