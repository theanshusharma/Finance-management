<?php
// get_user.php
include 'db_connection.php'; // Include your database connection file

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT * FROM users_details_monthly WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'User not found']);
    }

    $stmt->close();
}
$conn->close();
?>
