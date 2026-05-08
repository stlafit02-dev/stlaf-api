<?php
// ✅ CRITICAL: Force JSON + CORS
header('Content-Type: application/json; charset=utf8mb4');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ✅ Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host   = 'bchbyrvggka3okcjwmwv-mysql.services.clever-cloud.com';
$dbname = 'bchbyrvggka3okcjwmwv';
$dbuser = 'usdkgqrlhm5iiwtk';
$dbpass = 'dKzvf9Ns0GxUH041q5Hd';
// ✅ Safe DB connection
try {
    $conn = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "error" => "DB Connection failed: " . $e->getMessage()
    ], JSON_THROW_ON_ERROR);
    exit();
}

if (file_exists('cors.php')) include 'cors.php';

$method = $_SERVER['REQUEST_METHOD'];

/**
 * ============================
 * POST: Add/Update Employee (Your original logic)
 * ============================
 */
if ($method === 'POST') {
    try {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "No data received"]);
            exit();
        }

        $action = $data['action'] ?? '';
        $idNumber = trim($data['id_number'] ?? $data['username'] ?? '');
        $name = trim($data['name'] ?? '');
        $dept = trim($data['department'] ?? '');
        $pos = trim($data['position'] ?? '');
        $pass = (string)($data['password'] ?? '');

        if (($action === 'add' || $action === 'update') && 
            (empty($idNumber) || empty($name) || empty($dept) || empty($pos))) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Fill all required fields"]);
            exit();
        }

        $conn->beginTransaction();

        if ($action === 'add') {
            $check = $conn->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1");
            $check->execute([$idNumber]);
            if ($check->fetchColumn()) {
                $conn->rollBack();
                echo json_encode(["success" => false, "error" => "ID Number exists"]);
                exit();
            }

            $usernameVal = trim($data['username'] ?? $idNumber);
            $roleVal = trim($data['role'] ?? 'Employee');

            if (empty($pass)) {
                $conn->rollBack();
                echo json_encode(["success" => false, "error" => "Password required"]);
                exit();
            }

            $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (id_number, name, department, position, username, password, role)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$idNumber, $name, $dept, $pos, $usernameVal, $hashedPass, $roleVal]);

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Employee added!"]);
            exit();

        } elseif ($action === 'update') {
            $id = (int)($data['id'] ?? 0);
            if (!$id) {
                $conn->rollBack();
                echo json_encode(["success" => false, "error" => "Missing ID"]);
                exit();
            }

            $usernameVal = trim($data['username'] ?? $idNumber);
            $sql = "UPDATE users SET id_number=?, name=?, department=?, position=?, username=?";
            $params = [$idNumber, $name, $dept, $pos, $usernameVal, $id];

            if (!empty($pass)) {
                $params[4] = password_hash($pass, PASSWORD_DEFAULT); // Insert before ID
                $sql .= ", password=?";
            }

            $sql .= " WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Employee updated!"]);
            exit();
        }

        echo json_encode(["success" => false, "error" => "Invalid action"]);

    } catch(Exception $e) {
        if (isset($conn)) $conn->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit();
}

/**
 * ============================
 * GET: Admin Dashboard (ALL TYPES + 87 USERS FIX)
 * ============================
 */
$type = $_GET['type'] ?? 'all-leaves';
$search = trim($_GET['search'] ?? '');
$year = (int)($_GET['year'] ?? date('Y'));
$month = $_GET['month'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$payFilter = $_GET['pay_status'] ?? 'all';
$deptFilter = $_GET['department'] ?? 'all';

$response = [
    "stats" => ["total_users" => 0, "total_filed" => 0], 
    "data" => [],
    "debug" => [] // ✅ For troubleshooting
];

try {
    // ✅ STATS (accurate counts)
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE id_number != '' AND name != ''");
    $response['stats']['total_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $totalFiled = 0;
    $tables = ['leaves', 'overtimes', 'ob_logs'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM `$table`");
        $totalFiled += (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    $response['stats']['total_filed'] = $totalFiled;

    $searchTerm = "%{$search}%";
    $yearInt = max(2000, min(2030, $year)); // Safe year range

    // ✅ TYPE-SPECIFIC QUERIES (Your original logic + fixes)
    if ($type === 'manage-users') {
        // ✅ FIXED: Shows ALL 87 users when no filter
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
        $whereExtra = "";

        if ($deptFilter !== 'all' && !empty($deptFilter)) {
            $whereExtra = " AND department = ?";
            $params[] = $deptFilter;
        }

        $stmt = $conn->prepare("
            SELECT id, id_number, username, name, department, position, role
            FROM users
            WHERE (id_number != '' AND name != '')
              AND (
                id_number LIKE ? OR 
                username LIKE ? OR 
                name LIKE ? OR
                department LIKE ? OR
                position LIKE ?
              ) {$whereExtra}
            ORDER BY 
                CASE WHEN name REGEXP '^[0-9]' THEN 1 ELSE 0 END,
                name ASC
            LIMIT 100
        ");
        $stmt->execute($params);
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ✅ DEBUG INFO
        $response['debug'] = [
            'db_total_users' => $response['stats']['total_users'],
            'displayed_users' => count($response['data']),
            'search_term' => $search,
            'dept_filter' => $deptFilter,
            'difference' => $response['stats']['total_users'] - count($response['data'])
        ];

    } elseif ($type === 'all-leaves') {
        $params = [':search' => $searchTerm, ':year' => $yearInt];
        $whereExtra = [];

        if ($month !== 'all') {
            $monthInt = (int)$month;
            $whereExtra[] = "(MONTH(start_date) = :month OR MONTH(end_date) = :month)";
            $params[':month'] = $monthInt;
        }
        if ($statusFilter !== 'all') $whereExtra[] = "status = :status";
        if ($payFilter !== 'all') $whereExtra[] = "COALESCE(pay_status, 'Unpaid') = :pay_status";
        if ($deptFilter !== 'all') $whereExtra[] = "department = :dept";

        $whereClause = !empty($whereExtra) ? 'AND ' . implode(' AND ', $whereExtra) : '';
        if (isset($params[':status'])) $params[':status'] = $statusFilter;
        if (isset($params[':pay_status'])) $params[':pay_status'] = $payFilter;
        if (isset($params[':dept'])) $params[':dept'] = $deptFilter;

        $stmt = $conn->prepare("
            SELECT l.*, u.position,
                   COALESCE(l.pay_status, 'Unpaid') AS pay_status,
                   COALESCE(l.reason, '') AS reason,
                   COALESCE(l.rejection_reason, '') AS rejection_reason
            FROM leaves l LEFT JOIN users u ON l.employeeName = u.name
            WHERE YEAR(l.start_date) = :year {$whereClause}
              AND (l.employeeName LIKE :search OR l.department LIKE :search OR l.leave_type LIKE :search)
            ORDER BY l.id DESC LIMIT 100
        ");
        $stmt->execute($params);
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === 'all-overtime') {
        // Similar pattern for OT
        $params = [':search' => $searchTerm, ':year' => $yearInt];
        $whereExtra = [];
        
        if ($month !== 'all') {
            $whereExtra[] = "MONTH(ot_date) = :month";
            $params[':month'] = (int)$month;
        }
        if ($statusFilter !== 'all') $whereExtra[] = "status = :status";
        if ($deptFilter !== 'all') $whereExtra[] = "department = :dept";

        $whereClause = !empty($whereExtra) ? 'AND ' . implode(' AND ', $whereExtra) : '';
        if (isset($params[':status'])) $params[':status'] = $statusFilter;
        if (isset($params[':dept'])) $params[':dept'] = $deptFilter;

        $stmt = $conn->prepare("
            SELECT o.*, u.position,
                   COALESCE(o.reason, '') AS reason,
                   COALESCE(o.rejection_reason, '') AS rejection_reason
            FROM overtimes o LEFT JOIN users u ON o.employeeName = u.name
            WHERE YEAR(o.ot_date) = :year {$whereClause}
              AND (o.employeeName LIKE :search OR o.department LIKE :search)
            ORDER BY o.id DESC LIMIT 100
        ");
        $stmt->execute($params);
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === 'all-ob') {
        $params = [':search' => $searchTerm, ':year' => $yearInt];
        $whereExtra = [];
        
        if ($month !== 'all') {
            $whereExtra[] = "MONTH(date) = :month";
            $params[':month'] = (int)$month;
        }
        if ($statusFilter !== 'all') $whereExtra[] = "COALESCE(status, 'Recorded') = :status";
        if ($deptFilter !== 'all') $whereExtra[] = "u.department = :dept";

        $whereClause = !empty($whereExtra) ? 'AND ' . implode(' AND ', $whereExtra) : '';
        if (isset($params[':status'])) $params[':status'] = $statusFilter;
        if (isset($params[':dept'])) $params[':dept'] = $deptFilter;

        $stmt = $conn->prepare("
            SELECT ob.*, u.name AS employeeName, u.department, u.position
            FROM ob_logs ob LEFT JOIN users u ON u.id_number = ob.employee_id
            WHERE YEAR(ob.date) = :year {$whereClause}
              AND (u.name LIKE :search OR u.department LIKE :search)
            ORDER BY ob.date DESC LIMIT 100
        ");
        $stmt->execute($params);
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Query error: " . $e->getMessage(),
        "data" => []
    ], JSON_THROW_ON_ERROR);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "data" => []
    ], JSON_THROW_ON_ERROR);
}
?>