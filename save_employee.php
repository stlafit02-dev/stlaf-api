<?php
/**
 * file: save_employee.php
 * author: Iya
 * date: June 25, 2026
 * purpose: Registers new active staff profile data directly into the central user allocation directory tables.
 */
include 'cors.php';
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
  echo json_encode(["success" => false, "message" => "Invalid JSON body."]);
  exit;
}

$mode = trim((string)($data['mode'] ?? 'add')); 
$id   = $data['id'] ?? null;

$idNumber   = trim((string)($data['username'] ?? $data['id_number'] ?? '')); 
$name       = trim((string)($data['name'] ?? ''));
$department = trim((string)($data['department'] ?? ''));
$position   = trim((string)($data['position'] ?? ''));
$password   = (string)($data['password'] ?? '');
$role       = trim((string)($data['role'] ?? 'Employee')); 

// Basic validation
if ($idNumber === '' || $name === '' || $department === '' || $position === '') {
  echo json_encode(["success" => false, "message" => "Please fill in all required fields (ID Number, Name, Department, Position)."]);
  exit;
}

// For ADD, password is required
if ($mode !== 'edit' && trim($password) === '') {
  echo json_encode(["success" => false, "message" => "Password is required."]);
  exit;
}

try {
  // ✅ Ensure conn exists (db_config.php should provide $conn as mysqli)
  if (!isset($conn)) {
    echo json_encode(["success" => false, "message" => "Database connection not initialized."]);
    exit;
  }

  // ===== EDIT =====
  if ($mode === 'edit') {
    if (!$id) {
      echo json_encode(["success" => false, "message" => "Missing employee id for edit."]);
      exit;
    }

    // Prevent duplicate id_number on update
    $check = $conn->prepare("SELECT 1 FROM users WHERE id_number = ? AND id <> ? LIMIT 1");
    if (!$check) {
      echo json_encode(["success" => false, "message" => "Prepare failed", "mysql_error" => $conn->error]);
      exit;
    }
    $check->bind_param("si", $idNumber, $id);
    $check->execute();
    $res = $check->get_result();
    if ($res && $res->num_rows > 0) {
      echo json_encode(["success" => false, "message" => "ID Number already exists. Please use a different ID Number."]);
      exit;
    }
    $check->close();

    // Password optional
    if (trim($password) !== '') {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("
        UPDATE users
        SET id_number = ?,
            username  = ?,
            name      = ?,
            department= ?,
            position  = ?,
            password  = ?,
            role      = ?
        WHERE id = ?
      ");
      if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed", "mysql_error" => $conn->error]);
        exit;
      }
      $stmt->bind_param("sssssssi", $idNumber, $idNumber, $name, $department, $position, $hash, $role, $id);
    } else {
      $stmt = $conn->prepare("
        UPDATE users
        SET id_number = ?,
            username  = ?,
            name      = ?,
            department= ?,
            position  = ?,
            role      = ?
        WHERE id = ?
      ");
      if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed", "mysql_error" => $conn->error]);
        exit;
      }
      $stmt->bind_param("ssssssi", $idNumber, $idNumber, $name, $department, $position, $role, $id);
    }

    if ($stmt->execute()) {
      echo json_encode(["success" => true, "message" => "Employee updated successfully!"]);
    } else {
      echo json_encode(["success" => false, "message" => "Update failed", "mysql_error" => $stmt->error]);
    }
    $stmt->close();
    exit;
  }

  // ===== ADD =====
  // Duplicate check
  $check = $conn->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1");
  if (!$check) {
    echo json_encode(["success" => false, "message" => "Prepare failed", "mysql_error" => $conn->error]);
    exit;
  }
  $check->bind_param("s", $idNumber);
  $check->execute();
  $res = $check->get_result();
  if ($res && $res->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "ID Number already exists. Please use a different ID Number."]);
    exit;
  }
  $check->close();

  $hash = password_hash($password, PASSWORD_DEFAULT);

  // Insert user
  $stmt = $conn->prepare("
    INSERT INTO users (id_number, username, name, department, position, password, role)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed", "mysql_error" => $conn->error]);
    exit;
  }

  // username mirrors id_number to keep your login flexible
  $stmt->bind_param("sssssss", $idNumber, $idNumber, $name, $department, $position, $hash, $role);

  if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Employee added successfully!"]);
  } else {
    echo json_encode(["success" => false, "message" => "Insert failed", "mysql_error" => $stmt->error]);
  }
  $stmt->close();
  exit;

} catch (Throwable $e) {
  echo json_encode([
    "success" => false,
    "message" => "Server error.",
    "error"   => $e->getMessage(),
    "file"    => $e->getFile(),
    "line"    => $e->getLine(),
    "trace"   => $e->getTraceAsString(),
    "mysql_errno" => isset($conn) ? $conn->errno : null,
    "mysql_error" => isset($conn) ? $conn->error : null,
  ]);
  exit;
}
?>