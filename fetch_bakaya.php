<?php
include 'db.php';

$result = $conn->query("SELECT name, paise_lene, date FROM users_details WHERE paise_lene > 0");
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(['success' => true, 'data' => $data]);
?>