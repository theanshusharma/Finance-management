<?php
session_start();
require 'db.php'; // Ensure the database connection is included

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = "You do not have permission to access this page.";
    header("Location: index.php");
    exit();
}

// Get logged-in user ID from session
$logged_in_user_id = $_SESSION['user_id'];

// Handle user credential update
if (isset($_POST['update_user'])) {
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $username = !empty($_POST['username']) ? $_POST['username'] : null;

        if ($username) {
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $password, $logged_in_user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password, $logged_in_user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "User details updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update user details.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Settings</title>
    <link rel="icon" type="image/x-icon" href="assets/img/bank.png">
    <!-- Include Bootstrap CSS from a third-party theme -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/4.5.2/lux/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!-- Include custom CSS styles -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .hidden-section { display: none; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="text-white text-center py-3" style="background-color: black;">
        <h1 class="text-white text-center py-3">User Management</h1>
    </header>
    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Update User Details Section (for logged-in user only) -->
        <div id="update-user-section">
            <h3>Update Your Details</h3>
            <form method="post" action="settings.php">
                <div class="form-group">
                    <label for="username">New Username (optional):</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter new username">
                </div>
                <div class="form-group">
                    <label for="password">New Password:</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Enter new password">
                </div>
                <button type="submit" name="update_user" class="btn btn-primary btn-block">Update Details</button>
            </form>
        </div>

        <button type="button" class="btn btn-secondary btn-block mt-3" onclick="window.location.href='index.php'">Back to Main Page</button>
    </div>

    <!-- Include Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
