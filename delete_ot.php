<?php
include 'cors.php';
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "Missing overtime ID."]);
    exit;
}

// IMPORTANT: confirm your table name + primary key
// Based on your DB sidebar earlier, table is "overtimes"
$sql = "DELETE FROM overtimes WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Overtime request deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Overtime record not found or already deleted."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Execute failed: " . $stmt->error]);
}
?>