<?php
include 'cors.php';
include 'db_config.php';
// ✅ Suppress HTML errors so JSON stays clean
error_reporting(0);
ini_set('display_errors', 0);

// ✅ Set JSON header immediatelyheader('Content-Type: application/json');
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
$category   = isset($_GET['category'])   ? trim(strtolower($_GET['category'])) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date   = isset($_GET['end_date'])   ? trim($_GET['end_date'])   : '';

if (empty($category) || empty($start_date) || empty($end_date)) {
    echo json_encode(["message" => "Missing required parameters."]);
    exit;
}

// ==========================================
// ✅ MAP CATEGORY → TABLE + DATE COLUMN
// ==========================================
$allowed = [
    'leave'     => ['table' => 'leaves',    'date_col' => 'start_date'],
    'leaves'    => ['table' => 'leaves',    'date_col' => 'start_date'],
    'ob'        => ['table' => 'ob_logs',   'date_col' => 'date'],
    'ob_logs'   => ['table' => 'ob_logs',   'date_col' => 'date'],
    'overtime'  => ['table' => 'overtimes', 'date_col' => 'ot_date'],
    'overtimes' => ['table' => 'overtimes', 'date_col' => 'ot_date'],
    'users'     => ['table' => 'users',     'date_col' => null], // no date filter
];

if (!array_key_exists($category, $allowed)) {
    echo json_encode(["message" => "Invalid category: $category"]);
    exit;
}

$table    = $allowed[$category]['table'];
$date_col = $allowed[$category]['date_col'];

// ==========================================
// ✅ FETCH DATA
// ==========================================
try {
    if ($date_col === null) {
        // For 'users' table — no date filter
        $sql  = "SELECT * FROM `$table` ORDER BY id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    } else {
        $sql  = "SELECT * FROM `$table` 
                 WHERE `$date_col` BETWEEN :start AND :end 
                 ORDER BY `$date_col` DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':start', $start_date);
        $stmt->bindParam(':end',   $end_date);
        $stmt->execute();
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) === 0) {
        echo json_encode(["message" => "No records found in `$table` for the selected period."]);
        exit;
    }

    echo json_encode($data);

} catch (PDOException $e) {
    echo json_encode(["error" => "Query failed: " . $e->getMessage()]);
    exit;
}
?>
