<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $material_name = trim($_POST['materialName']);
    $physical_quantity = intval($_POST['physicalQuantity']);
    $reserved_quantity = intval($_POST['reservedQuantity']);
    $unit = trim($_POST['unit']);
    $re_order_level = intval($_POST['reOrderLevel']);
    $material_description = trim($_POST['materialDescription']);

    if ($reserved_quantity < $physical_quantity) {
        $response['message'] = "Reserved Quantity should be equal or greater than Physical Quantity.";
    } elseif (empty($material_name) || empty($unit)) {
        $response['message'] = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO materials (material_name, physical_quantity, reserved_quantity, unit, re_order_level, material_description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisis", $material_name, $physical_quantity, $reserved_quantity, $unit, $re_order_level, $material_description);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['id'] = $stmt->insert_id;
            $response['message'] = "Material added successfully!<br>ID: " . $stmt->insert_id;
        } else {
            $response['message'] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}
echo json_encode($response);
?>