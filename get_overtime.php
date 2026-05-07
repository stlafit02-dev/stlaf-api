<?php
include 'cors.php';
include 'db_config.php';

// Params
$empId  = trim($_GET['employeeId'] ?? '');
$role   = strtolower(trim($_GET['role'] ?? ''));
$dept   = trim($_GET['department'] ?? '');
$search = trim($_GET['search'] ?? '');
$year   = trim($_GET['year'] ?? date('Y'));
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$yearInt = (int)$year;
if ($yearInt <= 0) $yearInt = (int)date('Y');

$searchTerm = "%$search%";
$overtimes = [];

// If requesting a specific record (used by Edit)
if ($id > 0) {
    $sql = "SELECT
              o.*,
              COALESCE(o.status,'Pending') AS status,
              COALESCE(o.reason,'') AS reason,
              COALESCE(o.rejection_reason,'') AS rejection_reason
            FROM overtimes o
            WHERE o.id = ?
              AND YEAR(o.ot_date) = ?
              AND (? = '' OR o.employeeId = ?)
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed", "error" => $conn->error]);
        exit;
    }

    $stmt->bind_param("iiss", $id, $yearInt, $empId, $empId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    echo json_encode($row ? $row : []);
    exit;
}

try {
    if ($role === 'superadmin' || $role === 'admin') {
        $sql = "SELECT
                  o.*,
                  COALESCE(o.status,'Pending') AS status,
                  COALESCE(o.reason,'') AS reason,
                  COALESCE(o.rejection_reason,'') AS rejection_reason
                FROM overtimes o
                WHERE YEAR(o.ot_date) = ?
                  AND (o.employeeName LIKE ? OR o.employeeId LIKE ? OR o.department LIKE ? OR o.reason LIKE ? OR o.status LIKE ? OR o.rejection_reason LIKE ?)
                ORDER BY o.ot_date DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("issssss", $yearInt, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);

    } elseif ($role === 'approver') {
        $sql = "SELECT
                  o.*,
                  COALESCE(o.status,'Pending') AS status,
                  COALESCE(o.reason,'') AS reason,
                  COALESCE(o.rejection_reason,'') AS rejection_reason
                FROM overtimes o
                WHERE o.department = ?
                  AND YEAR(o.ot_date) = ?
                  AND (o.employeeName LIKE ? OR o.employeeId LIKE ? OR o.reason LIKE ? OR o.status LIKE ? OR o.rejection_reason LIKE ?)
                ORDER BY o.ot_date DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("sisssss", $dept, $yearInt, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);

    } else {
        if ($empId === '') {
            echo json_encode([]);
            exit;
        }
        $sql = "SELECT
                  o.*,
                  COALESCE(o.status,'Pending') AS status,
                  COALESCE(o.reason,'') AS reason,
                  COALESCE(o.rejection_reason,'') AS rejection_reason
                FROM overtimes o
                WHERE o.employeeId = ?
                  AND YEAR(o.ot_date) = ?
                ORDER BY o.ot_date DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("si", $empId, $yearInt);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (!isset($row['status']) || $row['status'] === '') $row['status'] = 'Pending';
        if (!isset($row['reason']) || $row['reason'] === null) $row['reason'] = '';
        if (!isset($row['rejection_reason']) || $row['rejection_reason'] === null) $row['rejection_reason'] = '';
        $overtimes[] = $row;
    }

    echo json_encode($overtimes);
} catch (Exception $e) {
    echo json_encode([]);
}
?>