<?php
ob_start(); // Start output buffering

require 'vendor/autoload.php'; // Include Composer's autoloader

// Create new PDF document
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Application');
$pdf->SetTitle('User Transaction History');
$pdf->SetSubject('PDF Export');
$pdf->SetKeywords('TCPDF, PDF, export, history');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Fetch user ID, name, and date range from query parameters
$userId = $_GET['user_id'] ?? 0;
$userName = $_GET['user_name'] ?? 'Unknown User';
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Validate user ID
if ($userId == 0) {
    die('Invalid User ID');
}

// Fetch user transaction history from the database
include 'db.php'; // Include your database connection

$query = "
    SELECT transaction_amount, transaction_date
    FROM transaction_history
    WHERE user_id = ?
";

if ($startDate) {
    $query .= " AND transaction_date >= ?";
}
if ($endDate) {
    $query .= " AND transaction_date <= ?";
}

$query .= " ORDER BY transaction_date";

$stmt = $conn->prepare($query);

if ($startDate && $endDate) {
    $stmt->bind_param("iss", $userId, $startDate, $endDate);
} elseif ($startDate) {
    $stmt->bind_param("is", $userId, $startDate);
} elseif ($endDate) {
    $stmt->bind_param("is", $userId, $endDate);
} else {
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$result = $stmt->get_result();

// Check if any data is returned
if ($result->num_rows == 0) {
    die('No transaction history found for this user.');
}

// Calculate total transaction amount
$totalAmount = 0;
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $totalAmount += $row['transaction_amount'];
    $transactions[] = $row;
}

// Add content to PDF
$html = '<h1>Transaction History for ' . htmlspecialchars($userName) . '</h1>';
$html .= '<p>Date: ' . date('d-m-Y') . '</p>';
$html .= '<p><strong>Total Transaction Amount: ' . number_format($totalAmount, 2) . '</strong></p>';
$html .= '<table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>Transaction Amount</th>
                    <th>Transaction Date</th>
                </tr>
            </thead>
            <tbody>';

foreach ($transactions as $transaction) {
    $transactionDate = $transaction['transaction_date'] ? (new DateTime($transaction['transaction_date']))->format('d-m-Y') : 'N/A';
    $html .= '<tr>
                <td>' . ($transaction['transaction_amount'] ?? 'N/A') . '</td>
                <td>' . $transactionDate . '</td>
              </tr>';
}

$html .= '</tbody></table>';

// Add HTML content to PDF
$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean(); // Clean the output buffer

// Close and output PDF document
$pdf->Output('user_transaction_history_' . $userId . '.pdf', 'I');
?>
