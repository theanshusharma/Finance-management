<?php
ob_start(); // Start output buffering

require 'vendor/autoload.php'; // Include Composer's autoloader

// Create new PDF document
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Application');
$pdf->SetTitle('User Transaction History');
$pdf->SetSubject('PDF Export');
$pdf->SetKeywords('TCPDF, PDF, export, user history');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Fetch user ID and name from query parameters
$userId = $_GET['user_id'] ?? null;
$userName = $_GET['user_name'] ?? 'Unknown User';

if (!$userId) {
    die('User ID is required for exporting history.');
}

// Fetch user transaction history from the database
include 'db.php'; // Include your database connection

$query = "
    SELECT transaction_amount, transaction_date
    FROM transaction_history
    WHERE user_id = ?
    ORDER BY transaction_date
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Add content to PDF
$html = '<h1>Transaction History for ' . htmlspecialchars($userName) . '</h1>';
$html .= '<p>Date: ' . date('d-m-Y') . '</p>';
$html .= '<table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>Transaction Amount</th>
                    <th>Transaction Date</th>
                </tr>
            </thead>
            <tbody>';

while ($row = $result->fetch_assoc()) {
    $transactionDate = $row['transaction_date'] ? (new DateTime($row['transaction_date']))->format('d-m-Y') : 'N/A';
    $html .= '<tr>
                <td>' . htmlspecialchars($row['transaction_amount']) . '</td>
                <td>' . $transactionDate . '</td>
              </tr>';
}

$html .= '</tbody></table>';

// Add HTML content to PDF
$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean(); // Clean the output buffer

// Close and output PDF document
$pdf->Output('user_transaction_history.pdf', 'I');
?> 