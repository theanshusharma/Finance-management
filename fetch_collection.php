<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $month = $_POST['month'];

    if (!empty($date)) {
        // Fetch daily collection
        $stmt = $conn->prepare("SELECT SUM(repayment_amount) as collection FROM repayment_history WHERE DATE(repayment_date) = ?");
        $stmt->bind_param("s", $date);
    } elseif (!empty($month)) {
        // Fetch monthly collection
        $stmt = $conn->prepare("SELECT SUM(repayment_amount) as collection FROM repayment_history WHERE DATE_FORMAT(repayment_date, '%Y-%m') = ?");
        $stmt->bind_param("s", $month);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid date or month']);
        exit;
    }
    $stmt->execute();
    $stmt->bind_result($collection);
    $stmt->fetch();
    $stmt->close();

    if ($collection !== null) {
        echo json_encode(['success' => true, 'collection' => $collection]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>