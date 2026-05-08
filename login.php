<?php
$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://192.168.100.38:5173',
    'http://192.168.137.1:5173',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $origin);
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit();
}

$username = $data['username'];
$password = $data['password'] ?? '';
$role = $data['role'];

if (!$username || !$password || !$role) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

$host   = 'bchbyrvggka3okcjwmwv-mysql.services.clever-cloud.com';
$dbname = 'bchbyrvggka3okcjwmwv';
$dbuser = 'usdkgqrlhm5iiwtk';
$dbpass = 'dKzvf9Ns0GxUH041q5Hd';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);
    exit();
}

$user = null;

// ==========================================
// EMPLOYEE LOGIN
// username = id_number
// ==========================================
if ($role === 'Employee') {
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE id_number = ?
        AND LOWER(role) IN ('employee', 'requestor/employee', 'requestor')
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ==========================================
// APPROVER LOGIN
// username = department
// ==========================================
elseif ($role === 'Approver') {
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE department = ?
        AND LOWER(role) = 'approver'
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ==========================================
// SUPERADMIN LOGIN
// username = username
// ==========================================
elseif ($role === 'superadmin') {
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE username = ?
        AND LOWER(role) = 'superadmin'
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit();
}

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'User not found.'
    ]);
    exit();
}

// ==========================================
// VERIFY PASSWORD
// Supports hashed and plain text passwords
// ==========================================
$dbPassword = $user['password'] ?? '';
$isPasswordValid = false;

if ($dbPassword !== '') {
    // If hashed password
    if (password_get_info($dbPassword)['algo']) {
        $isPasswordValid = password_verify($password, $dbPassword);
    }
    // If plain text password
    else {
        $isPasswordValid = hash_equals($dbPassword, $password);
    }
}

if (!$isPasswordValid) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid password.'
    ]);
    exit();
}

// ==========================================
// SUCCESS RESPONSE
// ==========================================
echo json_encode([
    'success' => true,
    'message' => 'Login successful.',
    'user' => [
        'id_number'  => $user['id_number'] ?? '',
        'name'       => $user['name'] ?? '',
        'role'       => $user['role'] ?? '',
        'department' => $user['department'] ?? '',
        'position'   => $user['position'] ?? ''
    ]
]);