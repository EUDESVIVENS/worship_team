<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once 'config/database.php';

$user_id = (int)$_POST['user_id'];
$voice_part = $_POST['voice_part'] ?: null;
$performance_level = $_POST['performance_level'];

$stmt = $pdo->prepare("UPDATE users SET voice_part = ?, performance_level = ? WHERE id = ?");
$result = $stmt->execute([$voice_part, $performance_level, $user_id]);

echo json_encode(['success' => $result]);
?>