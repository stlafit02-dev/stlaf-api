<?php
header("Access-Control-Allow-Origin: *");  
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit; // Agad na tapusin ang request para sa preflight checks
}

// 3. Database Credentials
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "stlaf_db"; 

// 4. Establishment of Connection (Object-Oriented style)
$conn = new mysqli($host, $user, $pass, $dbname);

// 5. Check Connection
if ($conn->connect_error) {
    // I-output natin ang error sa JSON format para mabasa ng React frontend mo
    die(json_encode([
        "success" => false, 
        "message" => "Database Connection Failed: " . $conn->connect_error
    ]));
}

// 6. Set Charset para iwas sa mga weird characters sa UI
$conn->set_charset("utf8mb4");
?>