<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;
require_once 'config/database.php';
$batch_id = $_GET['batch_id'];
$teams = $pdo->prepare("SELECT id, service_name FROM service_teams WHERE batch_id = ?");
$teams->execute([$batch_id]);
$allMembers = [];
while ($team = $teams->fetch()) {
    $members = $pdo->prepare("SELECT u.name, u.voice_part, u.performance_level FROM service_team_members stm JOIN users u ON stm.user_id = u.id WHERE stm.service_team_id = ?");
    $members->execute([$team['id']]);
    foreach ($members->fetchAll() as $m) {
        $allMembers[] = array_merge($m, ['team' => $team['service_name']]);
    }
}
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="batch_export.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['Name', 'Voice Part', 'Performance Level', 'Team']);
foreach ($allMembers as $row) {
    fputcsv($out, [$row['name'], $row['voice_part'], $row['performance_level'], $row['team']]);
}
fclose($out);
?>