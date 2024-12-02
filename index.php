<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home Page</title>
    <link rel="icon" type="image/x-icon" href="assets/img/bank.png">
    <!-- Include Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!-- Include a third-party theme for enhanced look and feel -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/4.5.2/lux/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="text-white text-center py-3" style="background-color: black;">
        <h1 class="text-white text-center py-3">Finance Management</h1>
    </header>

    <!-- Main Navigation -->
    <div class="row justify-content-center mt-4">
        <div class="text-center mb-4">
            <a href="daily.php" class="btn btn-primary btn-custom">
                <i class="fas fa-calendar-day"></i> Daily Repayment
            </a>
            <a href="monthly.php" class="btn btn-success btn-custom">
                <i class="fas fa-calendar-alt"></i> Monthly Interest Payment
            </a>
            <a href="user_management.php" class="btn btn-info btn-custom">
                <i class="fas fa-users"></i> Daily Bet Payment
            </a>
            
        </div>
    </div>

    <!-- Footer -->
    <footer class="mx-4">
        <div class="d-flex row justify-content-center mt-4">
            <a href="logout.php" class="btn btn-danger btn-custom mr-3">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            
            <?php if ($_SESSION['user_type'] === 'admin') { ?>
                <a href="settings.php" class="btn btn-warning btn-custom ml-3">
                    <i class="fas fa-cogs"></i> Settings
                </a>
            <?php } ?>
        </div>
    </footer>

    <!-- Include Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
