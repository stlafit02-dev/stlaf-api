<?php
// 1. Force JSON + CORS
header('Content-Type: application/json; charset=utf8mb4');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Database Credentials
$host   = 'bchbyrvggka3okcjwmwv-mysql.services.clever-cloud.com';
$dbname = 'bchbyrvggka3okcjwmwv';
$dbuser = 'usdkgqrlhm5iiwtk';
$dbpass = 'dKzvf9Ns0GxUH041q5Hd';

// 3. Safe DB connection
try {
    // FIX: Siniguro na tugma ang variable names ($dbname, $dbuser, $dbpass)
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "error" => "DB Connection failed: " . $e->getMessage()
    ]);
    exit();
}

// Isama ang cors kung kailangan pa, pero naka-set na sa taas
if (file_exists('cors.php')) include_once 'cors.php';

$method = $_SERVER['REQUEST_METHOD'];

/**
 * POST: Add/Update Employee
 */
if ($method === 'POST') {
    try {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (!$data) {
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
            echo json_encode(["success" => false, "error" => "Fill all required fields"]);
            exit();
        }

        if ($action === 'add') {
            $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (id_number, name, department, position, username, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$idNumber, $name, $dept, $pos, $idNumber, $hashedPass, 'Employee']);
            echo json_encode(["success" => true, "message" => "Employee added!"]);
            exit();
        }
        // ... (Update logic mo panatilihin mo lang)
    } catch(Exception $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit();
}

/**
 * GET: Admin Dashboard Data
 */
$type = $_GET['type'] ?? 'manage-users';
$search = trim($_GET['search'] ?? '');
$year = (int)($_GET['year'] ?? date('Y'));

$response = [
    "stats" => ["total_users" => 0, "total_filed" => 0], 
    "data" => []
];

try {
    // 1. STATS
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $response['stats']['total_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmtLeaves = $conn->query("SELECT COUNT(*) as total FROM leaves");
    $stmtOT = $conn->query("SELECT COUNT(*) as total FROM overtimes");
    $response['stats']['total_filed'] = (int)$stmtLeaves->fetch(PDO::FETCH_ASSOC)['total'] + (int)$stmtOT->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. MANAGE USERS
    if ($type === 'manage-users') {
        $searchTerm = "%$search%";
        $stmt = $conn->prepare("SELECT id, id_number, name, department, position, role FROM users WHERE name LIKE ? OR id_number LIKE ? ORDER BY name ASC");
        $stmt->execute([$searchTerm, $searchTerm]);
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    // Magdagdag ng else if para sa 'all-leaves' at 'all-overtime' base sa original logic mo...
    
    echo json_encode($response);

} catch(Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
