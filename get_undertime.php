<?php
include 'cors.php';
include 'db_config.php';

$host = "localhost";
$db_name = "stlaf_db";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    exit();
}

$employeeId = trim($_GET['employeeId'] ?? '');
$year = trim($_GET['year'] ?? date('Y'));

if ($employeeId === '') {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT
            id,
            employeeId,
            employeeName,
            department,
            leave_type,
            start_date,
            end_date,
            COALESCE(from_time,'') AS from_time,
            COALESCE(to_time,'') AS to_time,
            COALESCE(reason,'') AS reason,
            COALESCE(status,'Pending') AS status
        FROM leaves
        WHERE employeeId = :empId
          AND YEAR(start_date) = :yr
          AND leave_type IN ('Undertime', 'Halfday')
        ORDER BY id DESC
    ");
    $stmt->execute([
        ':empId' => $employeeId,
        ':yr'    => (int)$year
    ]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>