<?php
include 'db.php';

if (isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    
    // Fetch transaction history for the user
    $stmt = $conn->prepare("SELECT transaction_amount, transaction_date FROM transaction_history WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    $stmt->close();
    echo json_encode(['data' => $transactions]);
} else {
    echo json_encode(['data' => []]);
}
?>
