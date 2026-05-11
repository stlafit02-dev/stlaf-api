<?php
// ✅ Suppress HTML errors so JSON stays clean
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ==========================================
// ✅ DATABASE CONNECTION (PDO)
// ==========================================
$host   = 'bchbyrvggka3okcjwmwv-mysql.services.clever-cloud.com';
$dbname = 'bchbyrvggka3okcjwmwv';
$dbuser = 'usdkgqrlhm5iiwtk';
$dbpass = 'dKzvf9Ns0GxUH041q5Hd';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $dbuser,
        $dbpass
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
    exit;
}

// ==========================================
// ✅ GET PARAMETERS
// ==========================================
$category   = isset($_GET['category'])   ? trim($_GET['category'])   : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date   = isset($_GET['end_date'])   ? trim($_GET['end_date'])   : '';

if (empty($category) || empty($start_date) || empty($end_date)) {
    echo json_encode(["message" => "Missing required parameters."]);
    exit;
}

// ==========================================
// ✅ WHITELIST CATEGORY → TABLE
// ==========================================
$allowed_tables = [
    'leave' => 'leave_requests',   // 🔴 change to your actual table names
    'ob'    => 'ob_requests',
    'field' => 'field_requests',
    'users' => 'users',
];

if (!array_key_exists($category, $allowed_tables)) {
    echo json_encode(["message" => "Invalid category: $category"]);
    exit;
}

$table = $allowed_tables[$category];

// ==========================================
// ✅ FETCH DATA
// ==========================================
try {
    $sql  = "SELECT * FROM `$table` WHERE created_at BETWEEN :start AND :end";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start', $start_date);
    $stmt->bindParam(':end',   $end_date);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) === 0) {
        echo json_encode(["message" => "No records found."]);
        exit;
    }

    echo json_encode($data);

} catch (PDOException $e) {
    echo json_encode(["error" => "Query failed: " . $e->getMessage()]);
    exit;
}
?>
