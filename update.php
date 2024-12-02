<?php
include 'db.php';

// Function to calculate the number of full months
function calculateFullMonths($loanDate, $currentDate) {
    $loanDate = new DateTime($loanDate);
    $currentDate = new DateTime($currentDate);
    
    // Calculate the difference in full months
    $fullMonths = ($currentDate->format('Y') - $loanDate->format('Y')) * 12 + ($currentDate->format('m') - $loanDate->format('m'));
    
    return $fullMonths;
}

// Get the current date
$currentDate = date('Y-m-d');

// Prepare and execute the query to get user data
$stmt = $conn->prepare("SELECT id, remaining_balance, mahine_ka_byaj, date FROM users_details_monthly");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $remaining_balance = $row['remaining_balance'];
    $mahine_ka_byaj = $row['mahine_ka_byaj'];
    $loanDate = $row['date'];

    // Calculate the number of full months from the loan date to the current date
    $fullMonths = calculateFullMonths($loanDate, $currentDate);

    // Calculate the total interest for these full months
    $totalInterest = $fullMonths * $mahine_ka_byaj;

    // Update the remaining balance with the calculated interest
    $updateStmt = $conn->prepare("UPDATE users_details_monthly SET remaining_balance = ?, last_update = NOW() WHERE id = ?");
    $updateStmt->bind_param("di", $totalInterest, $id);
    $updateStmt->execute();
    $updateStmt->close();
}

$stmt->close();

echo "Database update complete.";
?>
