<?php
include 'cors.php';
include 'db_config.php';

$empId  = trim($_GET['employeeId']   ?? '');
$role   = strtolower(trim($_GET['role'] ?? ''));
$dept   = trim($_GET['department']  ?? '');
$search = trim($_GET['search']      ?? '');
$year   = trim($_GET['year']        ?? date('Y'));
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// mode:
//  - normal => exclude Undertime/Halfday
//  - ut     => only Undertime/Halfday
//  - all    => no filter (default)
$mode = strtolower(trim($_GET['mode'] ?? 'all'));

$yearInt = (int)$year;
if ($yearInt <= 0) $yearInt = (int)date('Y');

$searchTerm = '%' . $search . '%';

// leave_type filter
$filterTypeSql = "";
if ($mode === 'ut') {
    $filterTypeSql = " AND l.leave_type IN ('Undertime','Halfday') ";
} elseif ($mode === 'normal') {
    $filterTypeSql = " AND l.leave_type NOT IN ('Undertime','Halfday') ";
}

// base select (include rejection_reason)
$selectSql = "
    SELECT
        l.*,
        COALESCE(NULLIF(l.pay_status,''), 'Unpaid') AS pay_status,
        COALESCE(l.status, 'Pending') AS status,
        COALESCE(l.from_time, '') AS from_time,
        COALESCE(l.to_time, '') AS to_time,
        COALESCE(l.rejection_reason, '') AS rejection_reason
    FROM leaves l
";

// if requesting a specific record (Edit flow)
if ($id > 0) {
    $params = [];
    $types = "";

    $whereSql = " WHERE l.id = ? AND YEAR(l.start_date) = ? ";
    $params[] = $id;      $types .= "i";
    $params[] = $yearInt; $types .= "i";

    // employee-only restriction (keep your pattern)
    if (!($role === 'superadmin' || $role === 'admin' || $role === 'approver')) {
        if ($empId === '') { echo json_encode([]); exit; }
        $whereSql .= " AND l.employeeId = ? ";
        $params[] = $empId;
        $types .= "s";
    }

    $sql = $selectSql . $whereSql . " LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    echo json_encode($row ? $row : []);
    exit;
}

// list view
$params = [];
$types = "";

// base where
$whereSql = " WHERE YEAR(l.start_date) = ? ";
$params[] = $yearInt;
$types .= "i";

// role filters
if ($role === 'superadmin' || $role === 'admin') {
    // no extra filter
} elseif ($role === 'approver') {
    $whereSql .= " AND l.department = ? ";
    $params[] = $dept;
    $types .= "s";
} else {
    if ($empId === '') {
        echo json_encode([]);
        exit;
    }
    $whereSql .= " AND l.employeeId = ? ";
    $params[] = $empId;
    $types .= "s";
}

// search filter
if ($search !== '') {
    $whereSql .= "
      AND (
        COALESCE(l.employeeName,'') LIKE ? OR
        COALESCE(l.employeeId,'') LIKE ? OR
        COALESCE(l.department,'') LIKE ? OR
        COALESCE(l.leave_type,'') LIKE ? OR
        COALESCE(l.reason,'') LIKE ? OR
        COALESCE(l.status,'') LIKE ? OR
        COALESCE(l.rejection_reason,'') LIKE ?
      )
    ";
    for ($i = 0; $i < 7; $i++) {
        $params[] = $searchTerm;
        $types .= "s";
    }
}

$sql = $selectSql . $whereSql . $filterTypeSql . " ORDER BY l.id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$leaves = [];
while ($row = $result->fetch_assoc()) {
    if (!isset($row['pay_status']) || $row['pay_status'] === '') $row['pay_status'] = 'Unpaid';
    if (!isset($row['status']) || $row['status'] === '') $row['status'] = 'Pending';
    if (!isset($row['from_time']) || $row['from_time'] === null) $row['from_time'] = '';
    if (!isset($row['to_time']) || $row['to_time'] === null) $row['to_time'] = '';
    if (!isset($row['rejection_reason']) || $row['rejection_reason'] === null) $row['rejection_reason'] = '';
    $leaves[] = $row;
}

echo json_encode($leaves);
?>