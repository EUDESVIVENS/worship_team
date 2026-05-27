<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { echo json_encode([]); exit; }
require_once 'config/database.php';
$team_id = (int)$_GET['team_id'];
$stmt = $pdo->prepare("SELECT u.name, u.voice_part, u.performance_level FROM service_team_members stm JOIN users u ON stm.user_id = u.id WHERE stm.service_team_id = ?");
$stmt->execute([$team_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($members);
?>