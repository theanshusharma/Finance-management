<?php
// Include your database connection file
include 'db.php';  // Make sure this path is correct

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to the database<br>";

function checkAndUpdateMonthlyInterest($conn) {
    echo "Function started<br>";
    
    // Check if we've already updated this month
    $stmt = $conn->prepare("SELECT last_update FROM monthly_update_tracker WHERE id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $lastGlobalUpdate = new DateTime($row['last_update']);
    $currentDate = new DateTime();
    
    echo "Last update: " . $lastGlobalUpdate->format('Y-m-d') . "<br>";
    echo "Current date: " . $currentDate->format('Y-m-d') . "<br>";

    // If we've already updated this month, do nothing
    if ($lastGlobalUpdate->format('Y-m') === $currentDate->format('Y-m')) {
        echo "No update needed<br>";
        return "No update needed. Last update was on " . $lastGlobalUpdate->format('Y-m-d');
    }

    echo "Update needed, proceeding...<br>";

    // Check if there are any users to update
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users_details_monthly");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $userCount = $row['count'];
    echo "Number of users: " . $userCount . "<br>";

    if ($userCount == 0) {
        return "No users to update.";
    }

    // Proceed with the update
    $stmt = $conn->prepare("SELECT id, remaining_balance, mahine_ka_byaj FROM users_details_monthly");
    $stmt->execute();
    $result = $stmt->get_result();

    $updatedCount = 0;
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $remaining_balance = $row['remaining_balance'];
        $mahine_ka_byaj = $row['mahine_ka_byaj'];

        // Add the monthly interest
        $new_balance = $remaining_balance + $mahine_ka_byaj;

        // Update the remaining balance in the database
        $updateStmt = $conn->prepare("UPDATE users_details_monthly SET remaining_balance = ? WHERE id = ?");
        $updateStmt->bind_param("di", $new_balance, $id);
        $updateStmt->execute();
        $updatedCount++;

        echo "Updated user ID: $id, New balance: $new_balance<br>";
    }

    // Update the global last update date
    $updateTrackerStmt = $conn->prepare("UPDATE monthly_update_tracker SET last_update = CURDATE() WHERE id = 1");
    $updateTrackerStmt->execute();

    return "Monthly interest update completed. Updated $updatedCount user(s) on " . $currentDate->format('Y-m-d');
}

// Call the function and display the result
$result = checkAndUpdateMonthlyInterest($conn);
echo "<br>Final result: $result";
?>