<?php
include 'cors.php';
include 'db_config.php';

$host = "localhost";
$db_name = "stlaf_db";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Connection error: " . $e->getMessage()]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!is_array($data)) {
    echo json_encode(["success" => false, "message" => "Invalid JSON body."]);
    exit;
}

$id = $data['id'] ?? '';
$status = trim((string)($data['status'] ?? ''));
$type = strtolower(trim((string)($data['type'] ?? '')));

if ($id === '' || $status === '' || $type === '') {
    echo json_encode(["success" => false, "message" => "Incomplete data. ID, Status, and Type are required."]);
    exit;
}

// Determine table
$tableName = null;
if ($type === 'leave' || $type === 'leaves') $tableName = 'leaves';
if ($type === 'overtime' || $type === 'ot' || $type === 'overtimes') $tableName = 'overtimes';

if ($tableName === null) {
    echo json_encode(["success" => false, "message" => "Invalid type. Must be leave or overtime."]);
    exit;
}

// Normalize status values
$normalizedStatus = ucfirst(strtolower($status)); // "Approved" / "Rejected" / "Pending"

// ✅ Get rejection reason from many possible keys (frontend sends different names)
$rejectionReason =
    trim((string)($data['rejection_reason'] ?? '')) ?:
    trim((string)($data['reject_reason'] ?? '')) ?:
    trim((string)($data['rejected_reason'] ?? '')) ?:
    trim((string)($data['rejectReason'] ?? '')) ?:
    trim((string)($data['rejectionReason'] ?? '')) ?:
    trim((string)($data['remarks'] ?? ''));

// If rejected, require reason
if (strtolower($normalizedStatus) === 'rejected' && $rejectionReason === '') {
    echo json_encode(["success" => false, "message" => "Rejection reason is required."]);
    exit;
}

try {
    // ✅ If rejected: save rejection_reason
    // ✅ If approved/pending: clear rejection_reason (so table stays clean)
    $reasonToSave = (strtolower($normalizedStatus) === 'rejected') ? $rejectionReason : null;

    $query = "UPDATE {$tableName}
              SET status = :status,
                  rejection_reason = :rejection_reason
              WHERE id = :id";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':status', $normalizedStatus);
    $stmt->bindValue(':rejection_reason', $reasonToSave, $reasonToSave === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':id', $id);

    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Request has been " . strtolower($normalizedStatus) . " successfully."
    ]);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>