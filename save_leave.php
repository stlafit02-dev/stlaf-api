<?php
/**
 * file: save_leave.php
 * author: Iya
 * date: June 25, 2026
 * purpose: Processes and stores incoming employee leave requests into relational storage tracking lines.
 */
include 'cors.php';
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input data."]);
    exit;
}

// 🔍 DEBUG LOG (remove after fixing)
error_log("🔍 RAW DATA RECEIVED: " . print_r($data, true));

// Handle both field name formats (JS sends employee_id, PHP expects employeeId)
$empId = trim($data['employeeId'] ?? $data['employee_id'] ?? '');
$empName = trim($data['employeeName'] ?? $data['employee_name'] ?? ''); // Default or fetch from DB
$dept = trim($data['department'] ?? $data['dept'] ?? ''); // Default or fetch from DB
$lType = trim($data['leave_type'] ?? '');
$reason = trim($data['reason'] ?? '');
if ($reason === '') {
  echo json_encode(["success" => false, "message" => "Reason is required."]);
  exit;
}
$sDate = trim($data['start_date'] ?? '');
$eDate = trim($data['end_date'] ?? '');
$fromTime = trim($data['from_time'] ?? '');
$toTime = trim($data['to_time'] ?? '');
$payStatus = trim($data['pay_status'] ?? 'Unpaid');

// ✅ FIX 1: Auto-fetch employee details if missing (most secure)
if (empty($empName) || empty($dept)) {
    $userStmt = $conn->prepare("SELECT name, department FROM users WHERE id_number = ? LIMIT 1");
    $userStmt->bind_param("s", $empId);
    $userStmt->execute();
    $userResult = $userStmt->get_result()->fetch_assoc();
    
    if ($userResult) {
        $empName = $userResult['name'] ?? 'Unknown Employee';
        $dept = $userResult['department'] ?? 'Unknown Dept';
    } else {
        echo json_encode(["success" => false, "message" => "Employee ID not found in system."]);
        exit;
    }
}

// Basic validation
if (empty($empId) || empty($lType) || empty($reason) || empty($sDate)) {
    echo json_encode(["success" => false, "message" => "Please fill up all required fields."]);
    exit;
}

// Validate leave type behavior
$isTimeBased = in_array($lType, ['Undertime', 'Halfday'], true);

if ($isTimeBased) {
    // For undertime/halfday: require start_date + from/to time
    if (empty($fromTime) || empty($toTime)) {
        echo json_encode(["success" => false, "message" => "Please provide Start Time and End Time for Undertimes/Halfday."]);
        exit;
    }
    $eDate = $sDate; // Single day

    $sql = "INSERT INTO leaves (employeeId, employeeName, department, leave_type, start_date, end_date, from_time, to_time, reason, pay_status, status, date_filed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssss", $empId, $empName, $dept, $lType, $sDate, $eDate, $fromTime, $toTime, $reason, $payStatus);

} else {
    // Normal leaves: require start_date and end_date
    if (empty($eDate)) {
        echo json_encode(["success" => false, "message" => "Please provide End Date."]);
        exit;
    }

    $sql = "INSERT INTO leaves (employeeId, employeeName, department, leave_type, start_date, end_date, from_time, to_time, reason, pay_status, status, date_filed) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, 'Pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $empId, $empName, $dept, $lType, $sDate, $eDate, $reason, $payStatus);
}

if ($stmt->execute()) {

    require_once "send_email.php";

    sendRequestNotification(
        $conn,
        $dept,
        "New Leave Request",
        [
            "Employee" => $empName,
            "Department" => $dept,
            "Leave Type" => $lType,
            "Start Date" => $sDate,
            "End Date" => $eDate,
            "Reason" => $reason,
            "Status" => "Pending"
        ]
    );

    echo json_encode([
        "success" => true,
        "message" => "Leave request submitted successfully!"
    ]);

} else {

    error_log("❌ DB ERROR: " . $conn->error);

    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $conn->error
    ]);

}

$stmt->close();
$conn->close();
?>