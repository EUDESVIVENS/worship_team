<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

// Get filter parameters (same as user_management)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build WHERE clause
$where = [];
$params = [];
if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($roleFilter) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}
if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}
$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch all users matching filters (no limit)
$sql = "SELECT id, name, email, phone, date_of_birth, province, district, sector, village,
        gender, marital_status, membership_type, occupation, role, status, created_at 
        FROM users $whereClause ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, [
    'ID', 'Name', 'Email', 'Phone', 'Date of Birth', 
    'Province', 'District', 'Sector', 'Village',
    'Gender', 'Marital Status', 'Membership Type', 'Occupation',
    'Role', 'Status', 'Registration Date'
]);

// Data rows
foreach ($users as $user) {
    fputcsv($output, [
        $user['id'],
        $user['name'],
        $user['email'],
        $user['phone'],
        $user['date_of_birth'],
        $user['province'],
        $user['district'],
        $user['sector'],
        $user['village'],
        $user['gender'],
        $user['marital_status'],
        $user['membership_type'],
        $user['occupation'],
        $user['role'],
        $user['status'],
        $user['created_at']
    ]);
}

fclose($output);
exit;
?>