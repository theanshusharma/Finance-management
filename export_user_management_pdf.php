<?php
ob_start(); // Start output buffering

require 'vendor/autoload.php'; // Include Composer's autoloader

// Create new PDF document
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Application');
$pdf->SetTitle('User Management Details');
$pdf->SetSubject('PDF Export');
$pdf->SetKeywords('TCPDF, PDF, export, user management');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Fetch user details from the database
include 'db.php'; // Include your database connection

$query = "SELECT id, name, mobile_number, wallet FROM users_bet ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

// Add content to PDF
$html = '<h1>User Management Details</h1>';
$html .= '<p>Date: ' . date('d-m-Y') . '</p>';
$html .= '<table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>Sr.No.</th>
                    <th>Name</th>
                    <th>Mobile Number</th>
                    <th>Wallet</th>
                </tr>
            </thead>
            <tbody>';

$serialNumber = 1;
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . $serialNumber++ . '</td>
                <td>' . htmlspecialchars($row['name']) . '</td>
                <td>' . htmlspecialchars($row['mobile_number']) . '</td>
                <td>' . htmlspecialchars($row['wallet']) . '</td>
              </tr>';
}

$html .= '</tbody></table>';

// Add HTML content to PDF
$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean(); // Clean the output buffer

// Close and output PDF document
$pdf->Output('user_management_details.pdf', 'I');
?> 