<?php
include 'cors.php';
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
  echo json_encode(["success" => false, "message" => "Invalid JSON body."]);
  exit;
}

$id     = $data['id'] ?? null;
$otDate = trim($data['ot_date'] ?? '');
$hours  = $data['hours'] ?? null;
$reason = trim($data['reason'] ?? '');

if (!$id) {
  echo json_encode(["success" => false, "message" => "Missing overtime ID."]);
  exit;
}
if ($otDate === '' || $hours === null || $hours === '') {
  echo json_encode(["success" => false, "message" => "Missing required fields (ot_date, hours)."]);
  exit;
}

$hours = (float)$hours;

// ✅ IMPORTANT: table is overtimes (plural)
$sql = "UPDATE overtimes
        SET ot_date = ?, hours = ?, reason = ?
        WHERE id = ? AND LOWER(COALESCE(status,'Pending')) = 'pending'";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(["success" => false, "message" => "Prepare failed", "mysql_error" => $conn->error]);
  exit;
}

$stmt->bind_param("sdsi", $otDate, $hours, $reason, $id);

if ($stmt->execute()) {
  if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Overtime request updated!"]);
  } else {
    echo json_encode(["success" => false, "message" => "No changes made (maybe not pending or not found)."]);
  }
} else {
  echo json_encode(["success" => false, "message" => "Execute failed", "mysql_error" => $stmt->error]);
}
?>