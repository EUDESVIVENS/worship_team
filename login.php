<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['status'] == 'pending') {
                $error = "Your account is pending approval. Please wait for admin confirmation.";
            } elseif ($user['status'] == 'rejected') {
                $error = "Your account has been rejected. Please contact the administrator.";
            } elseif ($user['status'] == 'inactive') {
                $error = "Your account is inactive. Please contact the administrator.";
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: member_dashboard.php');
                }
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "No account found with this email. Please register first.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Reverence Worship Team</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(125deg, #0f0c29, #302b63, #24243e);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            z-index: -2;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border-radius: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 25px 45px rgba(0,0,0,0.2);
            transition: transform 0.4s ease;
        }
        .glass-card:hover { transform: translateY(-5px); }
        .input-field {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 1.25rem;
            padding: 0.9rem 1.2rem;
            color: white;
            font-weight: 500;
            width: 100%;
            outline: none;
            transition: all 0.3s;
        }
        .input-field:focus {
            border-color: #a855f7;
            background: rgba(255,255,255,0.15);
            box-shadow: 0 0 12px rgba(168,85,247,0.4);
        }
        .input-field::placeholder { color: rgba(255,255,255,0.5); }
        .btn-login {
            background: linear-gradient(90deg, #8b5cf6, #d946ef);
            border: none;
            border-radius: 1.25rem;
            padding: 0.9rem;
            font-weight: 700;
            color: white;
            transition: all 0.3s;
            cursor: pointer;
            width: 100%;
        }
        .btn-login:hover { transform: scale(1.02); box-shadow: 0 8px 25px rgba(139,92,246,0.4); }
        .error-toast {
            background: rgba(246, 178, 44, 0.9);
            backdrop-filter: blur(8px);
            border-radius: 1rem;
            padding: 0.8rem 1.2rem;
            color: white;
            font-size: 0.9rem;
            animation: slideDown 0.4s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .register-link { text-align: center; margin-top: 1.5rem; }
        .register-link a { color: #c084fc; text-decoration: none; font-weight: 500; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="animated-bg"></div>
<div class="relative min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-white/10 backdrop-blur rounded-3xl shadow-2xl mb-5 border border-white/20">
                <i class="fas fa-hands-praying text-5xl text-purple-300"></i>
            </div>
            <h1 class="text-4xl font-extrabold text-white tracking-tight drop-shadow-lg">Reverence Worship</h1>
            <p class="text-purple-200 mt-2 text-sm">Team Management System</p>
        </div>

        <div class="glass-card p-8 md:p-10">
            <h2 class="text-2xl font-semibold text-center text-white mb-2">Welcome Back</h2>
            <p class="text-center text-purple-200 text-sm mb-8">Sign in to continue</p>
            
            <?php if ($error): ?>
                <div class="error-toast mb-6 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-5">
                    <label class="block text-white/80 text-sm font-semibold mb-2">
                        <i class="fas fa-envelope mr-2 text-purple-300"></i> Email Address
                    </label>
                    <input type="email" name="email" required class="input-field" placeholder="you@example.com">
                </div>
                <div class="mb-6">
                    <label class="block text-white/80 text-sm font-semibold mb-2">
                        <i class="fas fa-lock mr-2 text-purple-300"></i> Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required class="input-field pr-12" placeholder="••••••••">
                        <i class="fas fa-eye-slash absolute right-4 top-1/2 -translate-y-1/2 text-white/60 hover:text-white cursor-pointer" id="togglePassword"></i>
                    </div>
                </div>
                <button type="submit" class="btn-login flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
            <div class="mt-6 text-center text-white/40 text-xs border-t border-white/20 pt-6">
                Demo: admin@worship.com / password123 &nbsp;|&nbsp; member@church.com / password123
            </div>
        </div>
    </div>
</div>

<script>
    const toggle = document.getElementById('togglePassword');
    const pwd = document.getElementById('password');
    if (toggle && pwd) {
        toggle.addEventListener('click', function() {
            const type = pwd.type === 'password' ? 'text' : 'password';
            pwd.type = type;
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    }
</script>
</body>
</html>