<?php
include 'cors.php';
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input data."]);
    exit;
}

$id = trim($data['id'] ?? $data['leaveId'] ?? '');
if (empty($id)) {
    echo json_encode(["success" => false, "message" => "No record ID provided."]);
    exit;
}

// Same field handling as save_leave.php
$empId = trim($data['employeeId'] ?? $data['employee_id'] ?? '');
$empName = trim($data['employeeName'] ?? $data['employee_name'] ?? '');
$dept = trim($data['department'] ?? $data['dept'] ?? '');
$lType = trim($data['leave_type'] ?? '');
$reason = trim($data['reason'] ?? '');
$sDate = trim($data['start_date'] ?? '');
$eDate = trim($data['end_date'] ?? '');
$fromTime = trim($data['from_time'] ?? '');
$toTime = trim($data['to_time'] ?? '');
$payStatus = trim($data['pay_status'] ?? 'Unpaid');

// Auto-fetch employee if missing
if (empty($empName) || empty($dept)) {
    $userStmt = $conn->prepare("SELECT name, department FROM users WHERE id_number = ? LIMIT 1");
    $userStmt->bind_param("s", $empId);
    $userStmt->execute();
    $userResult = $userStmt->get_result()->fetch_assoc();
    $empName = $userResult['name'] ?? 'Unknown';
    $dept = $userResult['department'] ?? 'Unknown';
}

if (empty($lType) || empty($reason) || empty($sDate)) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

$isTimeBased = in_array($lType, ['Undertime', 'Halfday']);

if ($isTimeBased) {
    $sql = "UPDATE leaves SET employeeId=?, employeeName=?, department=?, leave_type=?, start_date=?, end_date=?, from_time=?, to_time=?, reason=?, pay_status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $eDate = $sDate; // Single day
    $stmt->bind_param("ssssssssssi", $empId, $empName, $dept, $lType, $sDate, $eDate, $fromTime, $toTime, $reason, $payStatus, $id);
} else {
    $sql = "UPDATE leaves SET employeeId=?, employeeName=?, department=?, leave_type=?, start_date=?, end_date=?, reason=?, pay_status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $empId, $empName, $dept, $lType, $sDate, $eDate, $reason, $payStatus, $id);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Leave updated successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed: " . $conn->error]);
}
?>