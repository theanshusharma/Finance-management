<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['id'];
    $month = $_POST['month'];
    $year = $_POST['year'];

    // Fetch repayment history for the user for the selected month and year
    $stmt = $conn->prepare("SELECT repayment_amount, repayment_date FROM repayment_history WHERE user_id = ? AND MONTH(repayment_date) = ? AND YEAR(repayment_date) = ?");
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        exit();
    }

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'title' => $row['repayment_amount'],
            'start' => $row['repayment_date']
        ];
    }

    echo json_encode(['success' => true, 'data' => $events]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
