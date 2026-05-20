<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Insert: role = 'member', status = 'pending' (hardcoded)
            $sql = "INSERT INTO users (name, email, phone, date_of_birth, province, district, sector, village, 
                    gender, marital_status, membership_type, occupation, password, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'member', 'pending')";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$name, $email, $phone, $date_of_birth, $province, $district, $sector, $village, 
                                $gender, $marital_status, $membership_type, $occupation, $hashedPassword])) {
                $success = "Registration successful! Your account is pending admin approval.";
                // Clear form fields (optional)
                $name = $email = $phone = $date_of_birth = $province = $district = $sector = $village = 
                $gender = $marital_status = $membership_type = $occupation = '';
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Reverence Worship Team</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        .register-card { max-width: 800px; margin: 50px auto; background: white; border-radius: 16px; padding: 32px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.2rem; }
        label { font-weight: 600; display: block; margin-bottom: 0.4rem; color: #333; }
        input, select, textarea { width: 100%; padding: 0.7rem 1rem; border: 1px solid #ddd; border-radius: 8px; transition: 0.2s; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #2a5298; box-shadow: 0 0 0 3px rgba(42,82,152,0.1); }
        .btn { background: #1e3c72; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; width: 100%; }
        .btn:hover { background: #2a5298; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #dc3545; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #28a745; }
        .login-link { text-align: center; margin-top: 1.5rem; }
        .row { display: flex; gap: 1rem; flex-wrap: wrap; }
        .row .form-group { flex: 1; min-width: 200px; }
    </style>
</head>
<body>
<div class="register-card">
    <div class="text-center mb-6">
        <i class="fas fa-hands-praying text-4xl text-purple-600"></i>
        <h2 class="text-2xl font-bold mt-2">Join Worship Team</h2>
        <p class="text-gray-500">Register your account – pending admin approval</p>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" value="<?php echo isset($date_of_birth) ? $date_of_birth : ''; ?>">
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Province</label>
                <input type="text" name="province" value="<?php echo isset($province) ? htmlspecialchars($province) : ''; ?>">
            </div>
            <div class="form-group">
                <label>District</label>
                <input type="text" name="district" value="<?php echo isset($district) ? htmlspecialchars($district) : ''; ?>">
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Sector</label>
                <input type="text" name="sector" value="<?php echo isset($sector) ? htmlspecialchars($sector) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Village</label>
                <input type="text" name="village" value="<?php echo isset($village) ? htmlspecialchars($village) : ''; ?>">
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <option value="">Select</option>
                    <option value="Male" <?php echo (isset($gender) && $gender=='Male')?'selected':''; ?>>Male</option>
                    <option value="Female" <?php echo (isset($gender) && $gender=='Female')?'selected':''; ?>>Female</option>
                    <option value="Other" <?php echo (isset($gender) && $gender=='Other')?'selected':''; ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Marital Status</label>
                <select name="marital_status">
                    <option value="">Select</option>
                    <option value="Single" <?php echo (isset($marital_status) && $marital_status=='Single')?'selected':''; ?>>Single</option>
                    <option value="Married" <?php echo (isset($marital_status) && $marital_status=='Married')?'selected':''; ?>>Married</option>
                    <option value="Divorced" <?php echo (isset($marital_status) && $marital_status=='Divorced')?'selected':''; ?>>Divorced</option>
                    <option value="Widowed" <?php echo (isset($marital_status) && $marital_status=='Widowed')?'selected':''; ?>>Widowed</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Membership Type</label>
                <select name="membership_type">
                    <option value="Permanent" <?php echo (isset($membership_type) && $membership_type=='Permanent')?'selected':''; ?>>Permanent</option>
                    <option value="Friend" <?php echo (isset($membership_type) && $membership_type=='Friend')?'selected':''; ?>>Friend</option>
                </select>
            </div>
            <div class="form-group">
                <label>Occupation</label>
                <input type="text" name="occupation" value="<?php echo isset($occupation) ? htmlspecialchars($occupation) : ''; ?>">
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Password * (min 6 characters)</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required>
            </div>
        </div>
        <button type="submit" class="btn">Register</button>
    </form>
    <div class="login-link">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>
</body>
</html>