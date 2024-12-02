<?php
$password = 'admin'; // Replace this with the new password you want to set
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed Password: " . $hashedPassword;
?>
