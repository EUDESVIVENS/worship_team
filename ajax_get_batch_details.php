<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { echo json_encode([]); exit; }
require_once 'config/database.php';
$batch_id = $_GET['batch_id'];
$teams = $pdo->prepare("SELECT id, service_name FROM service_teams WHERE batch_id = ?");
$teams->execute([$batch_id]);
$result = [];
while ($team = $teams->fetch()) {
    $members = $pdo->prepare("SELECT u.name, u.voice_part, u.performance_level FROM service_team_members stm JOIN users u ON stm.user_id = u.id WHERE stm.service_team_id = ?");
    $members->execute([$team['id']]);
    foreach ($members->fetchAll() as $m) {
        $result[] = array_merge($m, ['team_name' => $team['service_name']]);
    }
}
header('Content-Type: application/json');
echo json_encode($result);
?>