<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Member Dashboard</title></head>
<body>
<h1>Welcome <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
<p>Your role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
<a href="logout.php">Logout</a>
</body>
</html>