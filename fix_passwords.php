<?php
require_once 'config/database.php';

// List of users to update
$users = [
    ['email' => 'admin@worship.com', 'name' => 'Admin User', 'role' => 'admin'],
    ['email' => 'member@church.com', 'name' => 'John Member', 'role' => 'member']
];

$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Using password: $password<br>";
echo "Generated hash: $hash<br><br>";

foreach ($users as $user) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$user['email']]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update existing user
        $update = $pdo->prepare("UPDATE users SET password = ?, name = ?, role = ?, status = 'active' WHERE email = ?");
        $update->execute([$hash, $user['name'], $user['role'], $user['email']]);
        echo "Updated: {$user['email']}<br>";
    } else {
        // Insert new user
        $insert = $pdo->prepare("INSERT INTO users (email, password, name, role, status) VALUES (?, ?, ?, ?, 'active')");
        $insert->execute([$user['email'], $hash, $user['name'], $user['role']]);
        echo "Inserted: {$user['email']}<br>";
    }
}

echo "<br><strong>Done! You can now log in with:<br>
admin@worship.com / password123<br>
member@church.com / password123</strong>";
?>