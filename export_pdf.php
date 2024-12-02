<?php
ob_start(); // Start output buffering

require 'vendor/autoload.php'; // Include Composer's autoloader

// Create new PDF document
$pdf = new TCPDF();

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Application');
$pdf->SetTitle('Exported Data');
$pdf->SetSubject('PDF Export');
$pdf->SetKeywords('TCPDF, PDF, export, data');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Fetch data from the database
include 'db.php'; // Include your database connection
$stmt = $conn->prepare("SELECT name, mobile_number, paise_diye, paise_lene, date FROM users_details");
$stmt->execute();
$result = $stmt->get_result();

// Add content to PDF
$html = '<h1>Exported Data</h1>';
$html .= '<table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Mobile Number</th>
                    <th>Paise Diye</th>
                    <th>Paise Lene</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . $row['name'] . '</td>
                <td>' . $row['mobile_number'] . '</td>
                <td>' . $row['paise_diye'] . '</td>
                <td>' . $row['paise_lene'] . '</td>
                <td>' . $row['date'] . '</td>
              </tr>';
}

$html .= '</tbody></table>';

// Add HTML content to PDF
$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean(); // Clean the output buffer

// Close and output PDF document
$pdf->Output('exported_data.pdf', 'I');
?>
