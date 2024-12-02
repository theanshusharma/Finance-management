<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    $stmt = $conn->prepare("SELECT * FROM users_details_monthly WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }

    echo json_encode([
        'success' => true,
        'details' => $details
    ]);

    $stmt->close();
}
?>
