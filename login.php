<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, user_type FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $user_type);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['user_type'] = $user_type;

        if ($user_type == 'admin') {
            header("Location: index.php");
        } elseif ($user_type == 'finance') {
            header("Location: daily.php");
        } elseif ($user_type == 'bet') {
            header("Location: user_management.php");
        }
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
    <title>Login To Your Finance Manager</title>
    <link rel="icon" type="image/x-icon" href="assets/img/bank.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/loginstyle.css">
</head>
<body>
    <div class="container">
        <div class="sign_up contact">
            <h1>Connect With Us</h1>
            <div class="icons">
                <a href=""><i class="fa-brands fa-google-plus-g"></i></a>
                <a href=""><i class="fa-brands fa-facebook"></i></a>
                <a href=""><i class="fa-brands fa-github"></i></a>
                <a href=""><i class="fa-brands fa-linkedin-in"></i></a>
            </div>
        </div>
        <div class="sign_in form_container">
            <form method="post" action="login.php">
                <h1>Sign In</h1>
                <input class="username" type="text" placeholder="Username" name="username" required>
                <p class="usernamefield text"><i class="fa-solid fa-circle-exclamation"></i> Please enter a valid username</p>
                <div class="passdiv">
                    <input class="pass1" type="password" placeholder="Password" name="password" required><i class="fa-solid fa-lock show-hide"></i>
                </div>
                <p class="passfield1 text"><i class="fa-solid fa-circle-exclamation"></i> Please enter a valid password</p>
                <button type="submit">SIGN IN</button>
            </form>
            <?php if (isset($error)) { echo "<div class='alert alert-danger mt-3 animate__animated animate__shakeX'>$error</div>"; } ?>
        </div>
        <div class="toggle_container">
            <div class="toggle">
                <div class="toggle_panel toggle_left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all of site features</p>
                    <button class="hidden" id="login">SIGN IN</button>
                </div>
                <div class="toggle_panel toggle_right">
                    <h1>Namastey!</h1>
                    <p>Thanks For Trusting Thecraftsync.</p>
                    <button class="hidden" id="register">Contact Us</button>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/login.js"></script>
</body>
</html>