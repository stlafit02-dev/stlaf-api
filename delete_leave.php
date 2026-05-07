<?php
include 'cors.php';
include 'db_config.php';

// Turn off PHP errors in output
error_reporting(0);

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(["success" => false, "message" => "Invalid request - no ID provided."]);
    exit;
}

$id = intval($data['id']); // Secure: convert to int

// 🔍 Check if record exists and is editable (Pending only)
$checkStmt = $conn->prepare("SELECT status FROM leaves WHERE id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result()->fetch_assoc();

if (!$result) {
    echo json_encode(["success" => false, "message" => "Record not found."]);
    exit;
}

if ($result['status'] !== 'Pending') {
    echo json_encode(["success" => false, "message" => "Cannot delete approved/rejected requests."]);
    exit;
}

// Delete the record
$deleteStmt = $conn->prepare("DELETE FROM leaves WHERE id = ?");
$deleteStmt->bind_param("i", $id);

if ($deleteStmt->execute()) {
    echo json_encode([
        "success" => true, 
        "message" => "Leave request deleted successfully."
    ]);
} else {
    error_log("DELETE ERROR: " . $conn->error);
    echo json_encode([
        "success" => false, 
        "message" => "Delete failed: " . $conn->error
    ]);
}

$deleteStmt->close();
$conn->close();
?>