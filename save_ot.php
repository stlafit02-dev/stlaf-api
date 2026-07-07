<?php
/**
 * file: save_ot.php
 * author: Iya
 * date: June 25, 2026
 * purpose: Adds new overtime work applications into transactional authorization pipelines.
 */
include 'cors.php';
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON body."]);
    exit;
}

$empId   = trim($data['employeeId'] ?? '');
$empName = trim($data['employeeName'] ?? '');
$dept    = trim($data['department'] ?? '');
$otDate  = trim($data['ot_date'] ?? '');
$hours   = $data['hours'] ?? null; // can be "2.5" string; we cast below
$reason = trim($data['reason'] ?? '');
if ($reason === '') {
  echo json_encode(["success" => false, "message" => "Reason is required."]);
  exit;
}

// validate
if ($empId === '' || $empName === '' || $dept === '' || $otDate === '' || $hours === null || $hours === '') {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

$hours = (float)$hours;

$sql = "INSERT INTO overtimes (employeeId, employeeName, department, ot_date, hours, reason, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed in save_ot.php",
        "mysql_error" => $conn->error
    ]);
    exit;
}

// employeeId(s), employeeName(s), department(s), ot_date(s), hours(d), reason(s)
$stmt->bind_param("ssssds", $empId, $empName, $dept, $otDate, $hours, $reason);

if ($stmt->execute()) {

    require_once "send_email.php";

    sendRequestNotification(
        $conn,
        $dept,
        "New Overtime Request",
        [
            "Employee" => $empName,
            "Department" => $dept,
            "OT Date" => $otDate,
            "Hours" => $hours,
            "Reason" => $reason,
            "Status" => "Pending"
        ]
    );

    echo json_encode([
        "success" => true,
        "message" => "Overtime request submitted!"
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Execute failed",
        "mysql_error" => $stmt->error
    ]);

}

$stmt->close();
$conn->close();
?>