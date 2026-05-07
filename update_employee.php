<?php
include 'cors.php';
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id_number'])) {
    $id_number = mysqli_real_escape_string($conn, $data['id_number']);
    $full_name = mysqli_real_escape_string($conn, $data['full_name']);
    $department = mysqli_real_escape_string($conn, $data['department']);
    $position = mysqli_real_escape_string($conn, $data['position']);
    $password = $data['password'];

    // Simulan ang query
    $sql = "UPDATE employees SET 
            employee_name = '$full_name', 
            department = '$department', 
            position = '$position'";

    // I-update lang ang password kung may input
    if (!empty($password)) {
        // Optional: password_hash kung encrypted ang storage mo
        $sql .= ", password = '$password'";
    }

    $sql .= " WHERE id_number = '$id_number'";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "Employee updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Missing ID Number"]);
}
?>