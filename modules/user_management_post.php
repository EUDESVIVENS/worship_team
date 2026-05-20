<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
       case 'add':
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $province = trim($_POST['province'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $marital_status = $_POST['marital_status'] ?? '';
    $membership_type = $_POST['membership_type'] ?? 'Permanent';
    $occupation = trim($_POST['occupation'] ?? '');
    $role = $_POST['role'];
    $status = $_POST['status'];
    $skills = trim($_POST['skills']);
    $plainPassword = bin2hex(random_bytes(6));
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    $avatarPath = '';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
                $avatarPath = 'uploads/avatars/' . $filename;
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, date_of_birth, province, district, sector, village, gender, marital_status, membership_type, occupation, password, role, status, skills, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $date_of_birth, $province, $district, $sector, $village, $gender, $marital_status, $membership_type, $occupation, $hashedPassword, $role, $status, $skills, $avatarPath]);
    $message = "User added. Temp password: $plainPassword (share securely)";
    break;
            
        case 'edit':
            $id = $_POST['id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $status = $_POST['status'];
            $skills = trim($_POST['skills']);
            
            $avatarPath = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/avatars/';
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed)) {
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
                        $avatarPath = 'uploads/avatars/' . $filename;
                        // Delete old avatar
                        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                        $stmt->execute([$id]);
                        $old = $stmt->fetchColumn();
                        if ($old && file_exists('../' . $old)) unlink('../' . $old);
                    }
                }
            }
            
            $sql = "UPDATE users SET name=?, email=?, role=?, status=?, skills=?";
            $params = [$name, $email, $role, $status, $skills];
            if ($avatarPath) {
                $sql .= ", avatar=?";
                $params[] = $avatarPath;
            }
            $sql .= " WHERE id=?";
            $params[] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $message = "User updated.";
            break;
            
        case 'delete':
            $id = $_POST['id'];
            if ($id == $_SESSION['user_id']) {
                $error = "Cannot delete your own admin account.";
            } else {
                $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $avatar = $stmt->fetchColumn();
                if ($avatar && file_exists('../' . $avatar)) unlink('../' . $avatar);
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $message = "User deleted.";
            }
            break;
            
        case 'reset_password':
            $id = $_POST['id'];
            $newPlain = bin2hex(random_bytes(4));
            $hashed = password_hash($newPlain, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $id]);
            $message = "Password reset. New temporary password: $newPlain";
            break;
            
        case 'approve':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$id]);
            $message = "User approved and activated.";
            break;

        case 'add':
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $date_of_birth = $_POST['date_of_birth'];
    $province = trim($_POST['province']);
    $district = trim($_POST['district']);
    $sector = trim($_POST['sector']);
    $village = trim($_POST['village']);
    $gender = $_POST['gender'];
    $marital_status = $_POST['marital_status'];
    $membership_type = $_POST['membership_type'];
    $occupation = trim($_POST['occupation']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $skills = trim($_POST['skills']);
    $plainPassword = bin2hex(random_bytes(6));
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    // Avatar upload same as before
    // Then insert:
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, date_of_birth, province, district, sector, village, gender, marital_status, membership_type, occupation, password, role, status, skills, avatar) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$name, $email, $phone, $date_of_birth, $province, $district, $sector, $village, $gender, $marital_status, $membership_type, $occupation, $hashedPassword, $role, $status, $skills, $avatarPath]);
    $message = "User added. Temp password: $plainPassword";
    break;

    case 'reject':
    $id = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$id]);
    $message = "User rejected.";
    break;
    
    case 'toggle_status':
    $id = $_POST['id'];
    // Get current status
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();
    $new_status = ($current == 'active') ? 'inactive' : 'active';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    $message = "User " . ($new_status == 'active' ? "activated" : "deactivated") . " successfully.";
    break;
    }
    header("Location: ?page=user_management&success=" . urlencode($message ?: $error));
    exit;
}
// If no action found, redirect back
header("Location: ?page=user_management");
exit;