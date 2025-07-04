<?php
header('Content-Type: application/json');
include 'db_connect.php';

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM materials WHERE material_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Material deleted successfully!";
    } else {
        $response['message'] = "Delete failed: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
echo json_encode($response);
?>