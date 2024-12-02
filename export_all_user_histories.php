<?php
ob_start(); // Start output buffering

require 'vendor/autoload.php'; // Include Composer's autoloader

// Create new PDF document
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Application');
$pdf->SetTitle('All Users Repayment Histories');
$pdf->SetSubject('PDF Export');
$pdf->SetKeywords('TCPDF, PDF, export, history');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Fetch the selected month from query parameters
$selectedMonth = $_GET['month'] ?? null;

// Fetch all users' details and repayment histories from the database
include 'db.php'; // Include your database connection

$query = "
    SELECT u.name, r.repayment_amount, r.repayment_date
    FROM users_details_monthly u
    LEFT JOIN repayment_history_monthly r ON u.id = r.user_id
";

if ($selectedMonth) {
    $query .= " WHERE DATE_FORMAT(r.repayment_date, '%m-%Y') = ?";
}

$query .= " ORDER BY u.name, r.repayment_date";

$stmt = $conn->prepare($query);

if ($selectedMonth) {
    $stmt->bind_param("s", $selectedMonth);
}

$stmt->execute();
$result = $stmt->get_result();

// Add content to PDF
$html = '<h1>All Users Repayment Histories</h1>';
$html .= '<p>Date: ' . date('d-m-Y') . '</p>';
$html .= '<table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>User Name</th>
                    <th>Repayment Amount</th>
                    <th>Repayment Date</th>
                </tr>
            </thead>
            <tbody>';

while ($row = $result->fetch_assoc()) {
    $repaymentDate = $row['repayment_date'] ? (new DateTime($row['repayment_date']))->format('d-m-Y') : 'N/A';
    $html .= '<tr>
                <td>' . htmlspecialchars($row['name']) . '</td>
                <td>' . ($row['repayment_amount'] ?? 'N/A') . '</td>
                <td>' . $repaymentDate . '</td>
              </tr>';
}

$html .= '</tbody></table>';

// Add HTML content to PDF
$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean(); // Clean the output buffer

// Close and output PDF document
$pdf->Output('all_users_repayment_histories.pdf', 'I');
?>