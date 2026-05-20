<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sub'])) {
        require_once __DIR__ . '/modules/music_evangelism_post.php';
        exit;
    } elseif (isset($_POST['action'])) {
        require_once __DIR__ . '/modules/user_management_post.php';
        exit;
    }
}
// Fetch dashboard stats
$totalMembers = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$totalSongs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
$totalPrayerRequests = $pdo->query("SELECT COUNT(*) FROM prayer_requests")->fetchColumn();
$upcomingEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE start_time > NOW()")->fetchColumn();
$totalDisciplineLogs = $pdo->query("SELECT COUNT(*) FROM discipline_logs")->fetchColumn();
$totalTransactions = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$totalAnnouncements = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();

$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Reverence Worship Team</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f0f2f5; width: 100%; }
        @import url('https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap');

        /* TOP NAVIGATION */
        .top-nav {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 18px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            min-height: 80px;
        }
        .logo { font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; }
        .logo i { font-size: 2rem; margin-right: 12px; }
        .user-controls { display: flex; gap: 18px; align-items: center; font-size: 1rem; }
        .logout-btn { background: rgba(255,255,255,0.15); padding: 10px 18px; border-radius: 12px; text-decoration: none; color: white; font-weight: 600; transition: 0.25s; }
        .logout-btn:hover { background: #dc3545; }

        /* CONTAINER - FULL WIDTH FLEX */
        html, body { width: 100%; margin: 0; padding: 0; overflow-x: auto; }
        .container { display: flex; width: 100%; min-height: calc(100vh - 80px); }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: white;
            height: auto;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding-top: 10px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 24px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar a i { font-size: 1.05rem; width: 22px; text-align: center; }
        .sidebar a:hover, .sidebar a.active {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            border-left-color: #ffd700;
            transform: translateX(4px);
        }

        /* MAIN CONTENT - FILLS REMAINING SPACE */
        .main-content {
            flex: 1;
            width: 0;          /* forces flex to use min-width:0 */
            min-width: 0;      /* allows content to shrink if needed */
            padding: 18px;
            background: transparent;
            overflow-x: auto;
        }

        /* CARDS */
        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 28px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            width: 100%;
            box-sizing: border-box;
        }
        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #1e3c72;
            padding-left: 1rem;
            color: #1e3c72;
        }

        /* BUTTONS */
        .btn { background: #1e3c72; color: white; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; transition: 0.2s; font-weight: 600; display: inline-block; text-decoration: none; }
        .btn:hover { background: #2a5298; transform: translateY(-1px); }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .edit-btn { background: #ffc107; color: #333; padding: 4px 10px; border-radius: 5px; font-size: 0.7rem; display: inline-block; margin: 2px; border: none; }
        .delete-btn { background: #dc3545; color: white; padding: 4px 10px; border-radius: 5px; font-size: 0.7rem; display: inline-block; margin: 2px; border: none; }

        /* FORMS */
        .form-group { margin-bottom: 1.2rem; }
        label { font-weight: 600; display: block; margin-bottom: 0.4rem; color: #333; }
        .form-control { width: 100%; padding: 0.7rem 1rem; border: 1px solid #ddd; border-radius: 8px; background: white; transition: 0.2s; }
        .form-control:focus { outline: none; border-color: #2a5298; box-shadow: 0 0 0 3px rgba(42,82,152,0.1); }

        /* ===== TABLE STYLES (RESTORED BEAUTIFUL STYLES) ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: auto;
        }
        .data-table th,
        .data-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #eef2f6;
        }
        .data-table th {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table tr {
            transition: all 0.2s ease;
        }
        .data-table tbody tr:hover {
            background: #f8fafc;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        /* Striped rows for better readability */
        .data-table tbody tr:nth-child(even) {
            background-color: #fafcff;
        }
        .data-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        /* BADGES */
        .badge { background: #e9ecef; color: #333; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
        .badge-admin { background: #dc3545; color: white; }
        .badge-worship_leader { background: #28a745; color: white; }
        .badge-treasurer { background: #ffc107; color: #333; }
        .badge-pastoral_overseer { background: #17a2b8; color: white; }
        .badge-member { background: #6c757d; color: white; }

        /* MODALS */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; border-radius: 20px; width: 95%; max-width: 850px; max-height: 92vh; overflow-y: auto; padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .modal-header { font-size: 1.5rem; font-weight: bold; margin-bottom: 16px; border-bottom: 1px solid #eee; padding-bottom: 12px; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

        /* UTILITIES */
        .flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .gap-3 { gap: 0.75rem; }
        .items-center { align-items: center; }
        .text-center { text-align: center; }
        .success-msg { background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #28a745; }
        .hidden { display: none; }
        .relative { position: relative; }
        .w-10 { width: 2.5rem; }
        .h-10 { height: 2.5rem; }
        .rounded-full { border-radius: 9999px; }
        .object-cover { object-fit: cover; }
        .shadow-sm { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .bg-gradient-to-br { background-image: linear-gradient(to bottom right, #a855f7, #3b82f6); }
        .from-purple-400 { --tw-gradient-from: #c084fc; }
        .to-indigo-500 { --tw-gradient-to: #6366f1; }
        .font-bold { font-weight: 700; }
        .text-white { color: white; }
        .justify-center { justify-content: center; }
        .items-center { align-items: center; }

        /* ACTION BUTTON */
        .actions-btn {
            background: linear-gradient(135deg, #2c2c4e, #5f59b6);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(99,102,241,0.25);
        }
        .actions-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(99,102,241,0.35); }

        /* DROPDOWN */
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 110%;
            min-width: 220px;
            background: white;
            border-radius: 14px;
            border: 1px solid #f1f1f1;
            overflow: auto;
            z-index: 999;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08), 0 4px 10px rgba(0,0,0,0.05);
            animation: dropdownFade 0.2s ease;
        }
        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dropdown-menu button {
            background: transparent;
            border: none;
            width: 100%;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .dropdown-menu button:hover { background: #f8fafc; padding-left: 22px; }
        .dropdown-menu hr { border: none; border-top: 1px solid #f1f5f9; margin: 4px 0; }
        .table-responsive {
            overflow-x: auto;
            overflow-y: visible;
            position: relative;
        }


        /* FORM GRID */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 18px;
        }
        .form-section {
            grid-column: 1 / -1;
            margin-top: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            font-size: 1rem;
            font-weight: 700;
            color: #1e3c72;
        }

        

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 16px; padding: 1.2rem; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.2s; cursor: pointer; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .stat-card i { font-size: 2rem; color: #2a5298; margin-bottom: 0.5rem; }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: #1e3c72; }
        .stat-label { color: #666; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .welcome-banner { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-radius: 16px; padding: 2rem; margin-bottom: 2rem; color: white; }
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
        .quick-btn { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 12px; padding: 0.8rem; text-align: center; text-decoration: none; color: #1e3c72; font-weight: 500; transition: 0.2s; display: block; }
        .quick-btn:hover { background: #eef2ff; border-color: #2a5298; transform: translateY(-2px); }
        .badge-pending { background: #ffc107; color: #333; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .container { flex-direction: column; }
            .main-content { padding: 24px; }
            .modal-content { padding: 18px; border-radius: 14px; }
            .form-grid { grid-template-columns: 1fr; }
            .data-table th, .data-table td { padding: 8px 12px; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body>
<div class="top-nav">
    <div class="logo"><i class="fas fa-hands-praying"></i> Reverence Worship Admin</div>
    <div class="user-controls">
        <span style="color:white;"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="container">
    <div class="sidebar">
        <a href="?page=dashboard" class="<?php echo $page=='dashboard'?'active':''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="?page=user_management" class="<?php echo $page=='user_management'?'active':''; ?>"><i class="fas fa-users"></i> User Management</a>
        <a href="?page=music_evangelism" class="<?php echo $page=='music_evangelism'?'active':''; ?>"><i class="fas fa-music"></i> Music & Evangelism</a>
        <a href="?page=intercession" class="<?php echo $page=='intercession'?'active':''; ?>"><i class="fas fa-pray"></i> Intercession & Growth</a>
        <a href="?page=social_fellowship" class="<?php echo $page=='social_fellowship'?'active':''; ?>"><i class="fas fa-heart"></i> Social Fellowship</a>
        <a href="?page=discipline" class="<?php echo $page=='discipline'?'active':''; ?>"><i class="fas fa-gavel"></i> Discipline</a>
        <a href="?page=financial" class="<?php echo $page=='financial'?'active':''; ?>"><i class="fas fa-coins"></i> Financial</a>
        <a href="?page=announcements" class="<?php echo $page=='announcements'?'active':''; ?>"><i class="fas fa-bullhorn"></i> Announcements</a>
        <a href="?page=settings" class="<?php echo $page=='settings'?'active':''; ?>"><i class="fas fa-cog"></i> Settings</a>
        <a href="?page=reports" class="<?php echo $page=='reports'?'active':''; ?>"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="?page=chats" class="<?php echo $page=='chats'?'active':''; ?>"><i class="fas fa-comments"></i> Chats</a>
    </div>
    <div class="main-content">
        <?php if ($page == 'dashboard'): ?>
            <div class="welcome-banner"><h1><i class="fas fa-crown"></i> Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1><p>Here's your worship team management hub.</p></div>
            <div class="stats-grid">
                <div class="stat-card" onclick="window.location.href='?page=user_management'"><i class="fas fa-user-friends"></i><div class="stat-number"><?php echo $totalMembers; ?></div><div class="stat-label">Team Members</div></div>
                <div class="stat-card" onclick="window.location.href='?page=music_evangelism'"><i class="fas fa-music"></i><div class="stat-number"><?php echo $totalSongs; ?></div><div class="stat-label">Songs</div></div>
                <div class="stat-card" onclick="window.location.href='?page=intercession'"><i class="fas fa-praying-hands"></i><div class="stat-number"><?php echo $totalPrayerRequests; ?></div><div class="stat-label">Prayer Requests</div></div>
                <div class="stat-card" onclick="window.location.href='?page=social_fellowship'"><i class="fas fa-calendar-alt"></i><div class="stat-number"><?php echo $upcomingEvents; ?></div><div class="stat-label">Upcoming Events</div></div>
                <div class="stat-card" onclick="window.location.href='?page=discipline'"><i class="fas fa-gavel"></i><div class="stat-number"><?php echo $totalDisciplineLogs; ?></div><div class="stat-label">Discipline Records</div></div>
                <div class="stat-card" onclick="window.location.href='?page=financial'"><i class="fas fa-coins"></i><div class="stat-number"><?php echo $totalTransactions; ?></div><div class="stat-label">Transactions</div></div>
                <div class="stat-card" onclick="window.location.href='?page=announcements'"><i class="fas fa-bullhorn"></i><div class="stat-number"><?php echo $totalAnnouncements; ?></div><div class="stat-label">Announcements</div></div>
            </div>
            <div class="card"><h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="quick-actions">
                    <a href="?page=user_management" class="quick-btn"><i class="fas fa-user-plus"></i> Add Member</a>
                    <a href="?page=music_evangelism" class="quick-btn"><i class="fas fa-plus"></i> Add Song</a>
                    <a href="?page=intercession" class="quick-btn"><i class="fas fa-pray"></i> New Prayer Request</a>
                    <a href="?page=social_fellowship" class="quick-btn"><i class="fas fa-calendar-plus"></i> Create Event</a>
                    <a href="?page=financial" class="quick-btn"><i class="fas fa-dollar-sign"></i> Add Transaction</a>
                    <a href="?page=announcements" class="quick-btn"><i class="fas fa-bullhorn"></i> Post Announcement</a>
                </div>
            </div>
        <?php elseif ($page == 'user_management'): ?>
            <?php include 'modules/user_management.php'; ?>
        <?php elseif ($page == 'music_evangelism'): ?>
            <?php include 'modules/music_evangelism.php'; ?>    
        <?php else: ?>
            <div class="card"><h2>Coming Soon</h2><p>This module is under construction.</p></div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>