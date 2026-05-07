<?php
include 'cors.php';
include 'db_config.php';

$dept = trim($_GET['department'] ?? '');
$type = trim($_GET['type'] ?? '');
$year = trim($_GET['year'] ?? date('Y'));

$yearInt = (int)$year;
if ($yearInt <= 0) $yearInt = (int)date('Y');

$response = [];

if (strpos($type, 'leave') !== false) {
    $status_filter = ($type === 'pending-leave') ? " = 'Pending'" : " != 'Pending'";

    $sql = "
        SELECT
            id,
            employeeId,
            employeeName AS employee_name,
            leave_type AS category,
            start_date,
            end_date,
            reason,
            status,
            pay_status,
            date_filed,
            COALESCE(rejection_reason,'') AS rejection_reason
        FROM leaves
        WHERE department = ?
          AND YEAR(start_date) = ?
          AND status $status_filter
        ORDER BY id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $dept, $yearInt);
    $stmt->execute();
    $result = $stmt->get_result();
    $response = $result->fetch_all(MYSQLI_ASSOC);

} else if (strpos($type, 'ot') !== false) {
    $status_filter = ($type === 'pending-ot') ? " = 'Pending'" : " != 'Pending'";

    $sql = "
        SELECT
            id,
            employeeId,
            employeeName AS employee_name,
            'Overtime' AS category,
            ot_date,
            hours,
            reason,
            status,
            'N/A' AS pay_status,
            COALESCE(rejection_reason,'') AS rejection_reason
        FROM overtimes
        WHERE department = ?
          AND YEAR(ot_date) = ?
          AND status $status_filter
        ORDER BY id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $dept, $yearInt);
    $stmt->execute();
    $result = $stmt->get_result();
    $response = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($response);
$conn->close();
?>