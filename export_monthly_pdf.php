<?php
ob_start(); // Start output buffering

require 'vendor/autoload.php'; // Include Composer's autoloader

// Create new PDF document
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Application');
$pdf->SetTitle('Monthly Details');
$pdf->SetSubject('PDF Export');
$pdf->SetKeywords('TCPDF, PDF, export, monthly');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Fetch data from the database
include 'db.php'; // Include your database connection

// Determine export type
$exportType = $_POST['exportType'] ?? 'all';

// Prepare SQL query based on export type
if ($exportType === 'history') {
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.mobile_number, r.repayment_amount, r.repayment_date
        FROM users_details_monthly u
        LEFT JOIN repayment_history_monthly r ON u.id = r.user_id
        WHERE r.repayment_amount IS NOT NULL
        ORDER BY u.id, r.repayment_date
    ");
    $pdf->SetTitle('Repayment History');
    $html = '<h1>Repayment History</h1>';
} else {
    $stmt = $conn->prepare("SELECT * FROM users_details_monthly");
    $pdf->SetTitle('All Monthly Details');
    $html = '<h1>All Monthly Details</h1>';
}

$stmt->execute();
$result = $stmt->get_result();

// Add content to PDF
$html .= '<p>Date: ' . date('d-m-Y') . '</p>';
$html .= '<table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>Sr.No.</th>
                    <th>Name</th>
                    <th>Mobile No.</th>';

if ($exportType === 'history') {
    $html .= '<th>Repayment Amount</th>
              <th>Repayment Date</th>';
} else {
    $html .= '<th>Paise Diye</th>
              <th>Byaj</th>
              <th>Balance</th>
              <th>Date</th>';
}

$html .= '</tr></thead><tbody>';

$serialNumber = 1;
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . $serialNumber++ . '</td>
                <td>' . $row['name'] . '</td>
                <td>' . $row['mobile_number'] . '</td>';

    if ($exportType === 'history') {
        $repaymentDate = $row['repayment_date'] ? (new DateTime($row['repayment_date']))->format('d-m-Y') : 'N/A';
        $html .= '<td>' . ($row['repayment_amount'] ?? 'N/A') . '</td>
                  <td>' . $repaymentDate . '</td>';
    } else {
        $userDate = new DateTime($row['date']);
        $formattedUserDate = $userDate->format('d-m-Y');
        $html .= '<td>' . $row['paise_diye'] . '</td>
                  <td>' . $row['mahine_ka_byaj'] . '</td>
                  <td>' . $row['remaining_balance'] . '</td>
                  <td>' . $formattedUserDate . '</td>';
    }

    $html .= '</tr>';
}

$html .= '</tbody></table>';

// Add HTML content to PDF
$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean(); // Clean the output buffer

// Close and output PDF document
$pdf->Output('monthly_details.pdf', 'I');
?>