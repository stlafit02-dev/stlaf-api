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
  echo json_encode(["success" => false, "message" => "Invalid JSON body."]);
  exit;
}

// Frontend sends employeeId, but DB column is employee_id
$employeeId = trim($data['employeeId'] ?? '');
$purpose    = trim($data['purpose'] ?? '');
$date       = trim($data['date'] ?? '');
$timeIn     = trim($data['time_in'] ?? '');
$timeOut    = trim($data['time_out'] ?? '');

// ✅ Required fields
if ($employeeId === '' || $purpose === '' || $date === '') {
  echo json_encode(["success" => false, "message" => "Employee ID, Purpose, and Date are required."]);
  exit;
}

// ✅ Optional: require time_in and time_out (recommended since your form requires them)
if ($timeIn === '' || $timeOut === '') {
  echo json_encode(["success" => false, "message" => "Time In and Time Out are required."]);
  exit;
}

// ✅ Validate time order (only if both provided)
if ($timeIn >= $timeOut) {
  echo json_encode(["success" => false, "message" => "Time In must be earlier than Time Out."]);
  exit;
}

// ✅ Always save as Recorded (matches your rule)
$sql = "INSERT INTO ob_logs (employee_id, purpose, date, time_in, time_out, status)
        VALUES (?, ?, ?, ?, ?, 'Recorded')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(["success" => false, "message" => "Prepare failed", "mysql_error" => $conn->error]);
  exit;
}

$stmt->bind_param("sssss", $employeeId, $purpose, $date, $timeIn, $timeOut);

if ($stmt->execute()) {

    // Get employee details
    $userStmt = $conn->prepare("
        SELECT name, department
        FROM users
        WHERE id_number = ?
        LIMIT 1
    ");

    $userStmt->bind_param("s", $employeeId);
    $userStmt->execute();

    $user = $userStmt->get_result()->fetch_assoc();

    if ($user) {

        require_once "send_email.php";

        sendRequestNotification(
            $conn,
            $user["department"],
            "New Official Business Request",
            [
                "Employee" => $user["name"],
                "Department" => $user["department"],
                "Purpose" => $purpose,
                "Date" => $date,
                "Time" => "$timeIn - $timeOut",
                "Status" => "Recorded"
            ]
        );
    }

    echo json_encode([
        "success" => true,
        "message" => "OB / Field request submitted!"
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Execute failed",
        "mysql_error" => $stmt->error
    ]);

}
?>