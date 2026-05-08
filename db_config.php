<?php
// 1. CORS Headers
header("Access-Control-Allow-Origin: *");  
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json"); // Siguraduhin na JSON lagi ang output

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit; 
}

// 2. Database Credentials (Clever Cloud)
$host   = 'bchbyrvggka3okcjwmwv-mysql.services.clever-cloud.com';
$dbname = 'bchbyrvggka3okcjwmwv';
$dbuser = 'usdkgqrlhm5iiwtk';
$dbpass = 'dKzvf9Ns0GxUH041q5Hd';

// 3. Establishment of Connection (MySQLi style - para sa get_leaves.php, etc.)
// FIX: Ginagamit na nito ang tamang variables ($dbuser at $dbpass)
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

// 4. Check MySQLi Connection
if ($conn->connect_error) {
    echo json_encode([
        "success" => false, 
        "message" => "Database Connection Failed: " . $conn->connect_error
    ]);
    exit;
}

if ($conn) {
    $conn->set_charset("utf8mb4");
}

// 5. Establishment of Connection (PDO style - para sa login.php)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Hindi na natin kailangan mag-exit dito kung gumana na ang mysqli sa itaas
}
?>