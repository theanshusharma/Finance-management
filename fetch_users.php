<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Fetch all users from the database
    $query = "SELECT id, name FROM users_bet";
    $result = $conn->query($query);

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users);
}
?>
