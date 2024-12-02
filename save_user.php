<?php
// save_user.php
include 'db_connection.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $mobile_number = $_POST['mobile_number'];
    $paise_diye = $_POST['paise_diye'];
    $mahine_ka_byaj = $_POST['mahine_ka_byaj'];
    $date = $_POST['date'];

    if (empty($id)) {
        // Insert new user
        $query = "INSERT INTO users_details_monthly (name, mobile_number, paise_diye, mahine_ka_byaj, date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssdds', $name, $mobile_number, $paise_diye, $mahine_ka_byaj, $date);
    } else {
        // Update existing user
        $query = "UPDATE users_details_monthly SET name = ?, mobile_number = ?, paise_diye = ?, mahine_ka_byaj = ?, date = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssddsi', $name, $mobile_number, $paise_diye, $mahine_ka_byaj, $date, $id);
    }

    if ($stmt->execute()) {
        session_start();
        $_SESSION['success'] = 'User details saved successfully!';
    } else {
        session_start();
        $_SESSION['error'] = 'Failed to save user details.';
    }

    $stmt->close();
}
$conn->close();

header('Location: index.php'); // Redirect back to the main page
?>
