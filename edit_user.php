<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $paise_diye = $_POST['paise_diye'];
    $mahine_ka_byaj = $_POST['mahine_ka_byaj'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("UPDATE users_details_monthly SET name = ?, paise_diye = ?, mahine_ka_byaj = ?, date = ? WHERE id = ?");
    $stmt->bind_param("sddsi", $name, $paise_diye, $mahine_ka_byaj, $date, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user details.']);
    }
}
?>