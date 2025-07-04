<?php
header('Content-Type: application/json');
include 'db_connect.php';

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['material_id']);
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
        $stmt = $conn->prepare("UPDATE materials SET material_name=?, physical_quantity=?, reserved_quantity=?, unit=?, re_order_level=?, material_description=? WHERE material_id=?");
        $stmt->bind_param("siisisi", $material_name, $physical_quantity, $reserved_quantity, $unit, $re_order_level, $material_description, $id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Material updated successfully!";
        } else {
            $response['message'] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}
echo json_encode($response);
?>