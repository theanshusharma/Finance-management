<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['id'];

    // Fetch repayment history for the user
    $stmt = $conn->prepare("SELECT repayment_amount, repayment_date FROM monthly_repayments WHERE user_id = ?");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        exit();
    }

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'title' => 'Repayment: ' . $row['repayment_amount'],
            'start' => $row['repayment_date'],
            'repayment' => true
        ];
    }

    echo json_encode(['success' => true, 'data' => $events]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>