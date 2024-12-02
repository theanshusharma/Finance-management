<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'] ?? null;
    $month = $_POST['month'] ?? null;

    if (!empty($date)) {
        // Fetch total transaction amount for the selected date
        $stmt = $conn->prepare("SELECT SUM(transaction_amount) as total_amount FROM transaction_history WHERE DATE(transaction_date) = ?");
        $stmt->bind_param("s", $date);
    } elseif (!empty($month)) {
        // Fetch total transaction amount for the selected month
        $stmt = $conn->prepare("SELECT SUM(transaction_amount) as total_amount FROM transaction_history WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?");
        $stmt->bind_param("s", $month);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid date or month']);
        exit;
    }

    $stmt->execute();
    $stmt->bind_result($total_amount);
    $stmt->fetch();
    $stmt->close();

    if ($total_amount !== null) {
        echo json_encode(['success' => true, 'total_amount' => (float)$total_amount]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No transactions found']);
    }
}
?>