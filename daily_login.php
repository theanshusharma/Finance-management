<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php'; // Ensure this file contains your database connection code

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare SQL statement to get user data from the daily_users table
    $stmt = $conn->prepare("SELECT id, password FROM daily_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
    $_SESSION['daily_user_id'] = $id;
    echo "Login successful. Redirecting to daily.php...";
    header("Location: daily.php");
    exit();
} else {
    $error = "Invalid username or password";
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily User Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/loginstyle.css">
</head>
<body>
    <div class="container">
        <div class="sign_in form_container">
            <form method="post" action="login_daily.php">
                <h1>Sign In</h1>
                <input class="username" type="text" placeholder="Username" name="username" required>
                <p class="usernamefield text"><i class="fa-solid fa-circle-exclamation"></i> Please enter a valid username</p>
                <div class="passdiv">
                    <input class="pass1" type="password" placeholder="Password" name="password" required>
                    <i class="fa-solid fa-lock show-hide"></i>
                </div>
                <p class="passfield1 text"><i class="fa-solid fa-circle-exclamation"></i> Please enter a valid password</p>
                <button type="submit">SIGN IN</button>
            </form>
            <?php if (isset($error)) { echo "<div class='alert alert-danger mt-3 animate__animated animate__shakeX'>$error</div>"; } ?>
        </div>
    </div>
    <script src="assets/js/login.js"></script>
</body>
</html>
