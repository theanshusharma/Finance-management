<?php
ob_start(); // Start output buffering

require 'vendor/autoload.php'; // Include Composer's autoloader

// Create new PDF document
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Application');
$pdf->SetTitle('Daily Details');
$pdf->SetSubject('PDF Export');
$pdf->SetKeywords('TCPDF, PDF, export, daily');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Fetch data from the database
include 'db.php'; // Include your database connection

// Correct the table name here
$stmt = $conn->prepare("SELECT * FROM users_details"); // Use the correct table name
$stmt->execute();
$result = $stmt->get_result();

// Add content to PDF
$html = '<h1>Daily Details</h1>';
$html .= '<p>Date: ' . date('d-m-Y') . '</p>';
$html .= '<table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>Sr.No.</th>
                    <th>Name</th>
                    <th>Mobile No.</th>
                    <th>Paise Diye</th>
                    <th>Paise Lene</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>';

$serialNumber = 1;
while ($row = $result->fetch_assoc()) {
    $userDate = new DateTime($row['date']);
    $formattedUserDate = $userDate->format('d-m-Y');
    $html .= '<tr>
                <td>' . $serialNumber++ . '</td>
                <td>' . htmlspecialchars($row['name']) . '</td>
                <td>' . htmlspecialchars($row['mobile_number']) . '</td>
                <td>' . htmlspecialchars($row['paise_diye']) . '</td>
                <td>' . htmlspecialchars($row['paise_lene']) . '</td>
                <td>' . $formattedUserDate . '</td>
              </tr>';
}

$html .= '</tbody></table>';

// Add HTML content to PDF
$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean(); // Clean the output buffer

// Close and output PDF document
$pdf->Output('daily_details.pdf', 'I');
?>
