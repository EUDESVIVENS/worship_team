<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

// Fetch counts for stats (overall, independent of filters)
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeCount = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$inactiveCount = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$rejectedCount = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'rejected'")->fetchColumn();

$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

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

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
$countStmt->execute($params);
$filteredTotal = $countStmt->fetchColumn();
$totalPages = ceil($filteredTotal / $limit);

$stmt = $pdo->prepare("SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<?php if (isset($_GET['success'])): ?>
    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?></div>
<?php endif; ?>



<!-- Filters -->
<div class="card" style="padding:1rem;">
    <form method="GET" action="?page=user_management" class="flex flex-wrap gap-3 items-center">
        <input type="hidden" name="page" value="user_management">
        <input type="text" name="search" class="form-control" style="width:200px;" placeholder="Search name or email" value="<?php echo htmlspecialchars($search); ?>">
        <select name="role" class="form-control" style="width:150px;">
            <option value="">All Roles</option>
            <option value="admin" <?php echo $roleFilter=='admin'?'selected':''; ?>>Admin</option>
            <option value="Music" <?php echo $roleFilter=='Music'?'selected':''; ?>>Music</option>
            <option value="Social" <?php echo $roleFilter=='Social'?'selected':''; ?>>Social</option>
            <option value="Spiritual Growth" <?php echo $roleFilter=='Spiritual Growth'?'selected':''; ?>>Spiritual Growth</option>
            <option value="member" <?php echo $roleFilter=='member'?'selected':''; ?>>Member</option>
        </select>
        <select name="status" class="form-control" style="width:140px;">
            <option value="">All Status</option>
            <option value="active" <?php echo $statusFilter=='active'?'selected':''; ?>>Active</option>
            <option value="inactive" <?php echo $statusFilter=='inactive'?'selected':''; ?>>Inactive</option>
            <option value="pending" <?php echo $statusFilter=='pending'?'selected':''; ?>>Pending</option>
            <option value="rejected" <?php echo $statusFilter=='rejected'?'selected':''; ?>>Rejected</option>
        </select>
        <button type="submit" class="btn">Filter</button>
        <a href="?page=user_management" class="btn btn-secondary">Reset</a>
        <button type="button" onclick="openAddModal()" class="btn" style="background:#28a745;">+ Add User</button>
        <a href="export_users.php?search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" class="btn" style="background:#17a2b8; color:white;">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </form>
</div>

<!-- Stats Cards -->
<div class="stats-grid" style="margin-bottom: 1rem;">

    <div class="stat-card flex items-center gap-4" style="background: white;">
        <i class="fas fa-users text-3xl text-blue-600"></i>
        <div>
            <div class="stat-label">Total Users</div>
            <div class="stat-number"><?php echo $totalUsers; ?></div>
        </div>
    </div>

    <div class="stat-card flex items-center gap-4" style="background: white;">
        <i class="fas fa-user-check text-3xl text-green-600"></i>
        <div>
            <div class="stat-label">Active</div>
            <div class="stat-number"><?php echo $activeCount; ?></div>
        </div>
    </div>

    <div class="stat-card flex items-center gap-4" style="background: white;">
        <i class="fas fa-user-slash text-3xl text-gray-600"></i>
        <div>
            <div class="stat-label">Inactive</div>
            <div class="stat-number"><?php echo $inactiveCount; ?></div>
        </div>
    </div>

    <div class="stat-card flex items-center gap-4" style="background: white;">
        <i class="fas fa-hourglass-half text-3xl text-yellow-600"></i>
        <div>
            <div class="stat-label">Pending</div>
            <div class="stat-number"><?php echo $pendingCount; ?></div>
        </div>
    </div>

    <div class="stat-card flex items-center gap-4" style="background: white;">
        <i class="fas fa-ban text-3xl text-red-600"></i>
        <div>
            <div class="stat-label">Rejected</div>
            <div class="stat-number"><?php echo $rejectedCount; ?></div>
        </div>
    </div>

</div>

<!-- Users Table (unchanged from your provided code, so I'll paste it as is for completeness) -->
<div class="card" style="overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr><th>Avatar</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Registered</th><th style="width:100px;">Actions</th></tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="7" class="text-center">No users found.<?php echo $search ? ' Try different filters.' : ''; ?></td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
            <tr class="hover:bg-gray-50 transition">
                <td>
                    <?php if (!empty($u['avatar']) && file_exists('../' . $u['avatar'])): ?>
                        <img src="../<?php echo $u['avatar']; ?>" class="w-10 h-10 rounded-full object-cover shadow-sm">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-400 to-indigo-500 flex items-center justify-center text-white font-bold shadow-sm">
                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="font-medium text-gray-900"><?php echo htmlspecialchars($u['name']); ?></td>
                <td class="text-gray-600"><?php echo htmlspecialchars($u['email']); ?></td>
                <td><span class="badge badge-<?php echo $u['role']; ?> px-3 py-1 rounded-full text-xs font-semibold"><?php echo ucfirst(str_replace('_',' ',$u['role'])); ?></span></td>
                <td>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold inline-block
                        <?php echo $u['status']=='active'?'bg-green-100 text-green-800':($u['status']=='pending'?'bg-yellow-100 text-yellow-800':($u['status']=='rejected'?'bg-red-100 text-red-800':'bg-gray-100 text-gray-800')); ?>">
                        <?php echo ucfirst($u['status']); ?>
                    </span>
                </td>
                <td class="text-gray-500 text-sm"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                <td class="relative">
                    <button onclick="toggleDropdown(<?php echo $u['id']; ?>)" class="actions-btn">
                        Actions <i class="fas fa-chevron-down text-xs ml-1"></i>
                    </button>
                    <div id="dropdown-<?php echo $u['id']; ?>" class="dropdown-menu hidden">
                        <div class="py-1">
                            <button onclick="viewUserDetails(<?php echo htmlspecialchars(json_encode($u)); ?>)" class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-gray-100 flex items-center gap-2">
                                <i class="fas fa-eye text-blue-500 w-4"></i> View Details
                            </button>
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($u)); ?>)" class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-gray-100 flex items-center gap-2">
                                <i class="fas fa-user-edit text-green-500 w-4"></i> Edit Roles
                            </button>
                            <?php if ($u['status'] == 'pending'): ?>
                                <button onclick="approveUser(<?php echo $u['id']; ?>)" class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-gray-100 flex items-center gap-2">
                                    <i class="fas fa-check-circle text-green-600 w-4"></i> Approve
                                </button>
                                <button onclick="rejectUser(<?php echo $u['id']; ?>)" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 flex items-center gap-2">
                                    <i class="fas fa-times-circle text-red-600 w-4"></i> Reject
                                </button>
                            <?php else: ?>
                                <?php if ($u['status'] == 'active'): ?>
                                    <button onclick="toggleUserStatus(<?php echo $u['id']; ?>)" class="w-full text-left px-4 py-2 text-sm text-orange-600 hover:bg-gray-100 flex items-center gap-2">
                                        <i class="fas fa-ban text-orange-500 w-4"></i> Deactivate
                                    </button>
                                <?php elseif ($u['status'] == 'inactive'): ?>
                                    <button onclick="toggleUserStatus(<?php echo $u['id']; ?>)" class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-gray-100 flex items-center gap-2">
                                        <i class="fas fa-check-circle text-green-600 w-4"></i> Activate
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <hr class="my-1">
                            <button onclick="deleteUser(<?php echo $u['id']; ?>)" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 flex items-center gap-2">
                                <i class="fas fa-trash-alt text-red-500 w-4"></i> Delete User
                            </button>
                        </div>
                    </div>
                 </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="mt-4 flex justify-center gap-2">
            <?php for ($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=user_management&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>" class="px-3 py-1 border rounded-md <?php echo $i==$page?'bg-purple-600 text-white border-purple-600':'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modals (same as before, unchanged) -->
<div id="viewDetailsModal" class="modal">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">User Detailss</div>
        <div id="detailsContent" style="max-height: 500px; overflow-y: auto;"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewDetailsModal')">Close</button>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">Add New User</div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group"><label>Full Name *</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
                <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" class="form-control"></div>
                <div class="form-group"><label>Province</label><input type="text" name="province" class="form-control"></div>
                <div class="form-group"><label>District</label><input type="text" name="district" class="form-control"></div>
                <div class="form-group"><label>Sector</label><input type="text" name="sector" class="form-control"></div>
                <div class="form-group"><label>Village</label><input type="text" name="village" class="form-control"></div>
                <div class="form-group"><label>Gender</label>
                    <select name="gender" class="form-control"><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
                </div>
                <div class="form-group"><label>Marital Status</label>
                    <select name="marital_status" class="form-control"><option value="">Select</option><option value="Single">Single</option><option value="Married">Married</option><option value="Divorced">Divorced</option><option value="Widowed">Widowed</option></select>
                </div>
                <div class="form-group"><label>Membership Type</label>
                    <select name="membership_type" class="form-control"><option value="Permanent">Permanent</option><option value="Friend">Friend</option></select>
                </div>
                <div class="form-group"><label>Occupation</label><input type="text" name="occupation" class="form-control"></div>
                <div class="form-group"><label>Role</label>
                    <select name="role" class="form-control">
                        <option value="member">Member</option>
                        <option value="Music">Music</option>
                        <option value="Social">Social</option>
                        <option value="Spiritual Growth">Spiritual Growth</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status" class="form-control">
                        <option value="pending" selected>Pending</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="form-group"><label>Skills</label><input type="text" name="skills" class="form-control" placeholder="vocals, guitar, drums"></div>
                <div class="form-group"><label>Avatar</label><input type="file" name="avatar" class="form-control" accept="image/*"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn">Add User</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">Edit User</div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group"><label>Full Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone" class="form-control"></div>
            <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" id="edit_dob" class="form-control"></div>
            <div class="form-group"><label>Province</label><input type="text" name="province" id="edit_province" class="form-control"></div>
            <div class="form-group"><label>District</label><input type="text" name="district" id="edit_district" class="form-control"></div>
            <div class="form-group"><label>Sector</label><input type="text" name="sector" id="edit_sector" class="form-control"></div>
            <div class="form-group"><label>Village</label><input type="text" name="village" id="edit_village" class="form-control"></div>
            <div class="form-group"><label>Gender</label><select name="gender" id="edit_gender" class="form-control"><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select></div>
            <div class="form-group"><label>Marital Status</label><select name="marital_status" id="edit_marital" class="form-control"><option value="">Select</option><option value="Single">Single</option><option value="Married">Married</option><option value="Divorced">Divorced</option><option value="Widowed">Widowed</option></select></div>
            <div class="form-group"><label>Membership Type</label><select name="membership_type" id="edit_membership" class="form-control"><option value="Permanent">Permanent</option><option value="Friend">Friend</option></select></div>
            <div class="form-group"><label>Occupation</label><input type="text" name="occupation" id="edit_occupation" class="form-control"></div>
            <div class="form-group"><label>Role</label><select name="role" id="edit_role" class="form-control">
                <option value="member">Member</option>
                <option value="Music">Music</option>
                <option value="Social">Social</option>
                <option value="Spiritual Growth">Spiritual Growth</option>
                <option value="admin">Admin</option>
            </select></div>
            <div class="form-group"><label>Status</label><select name="status" id="edit_status" class="form-control">
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="rejected">Rejected</option>
            </select></div>
            <div class="form-group"><label>New Avatar</label><input type="file" name="avatar" class="form-control" accept="image/*"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() { document.getElementById('addModal').style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    
    function viewUserDetails(user) {
        let details = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1rem;">
                <div><strong>Full Name:</strong></div><div>${user.name || '-'}</div>
                <div><strong>Email:</strong></div><div>${user.email || '-'}</div>
                <div><strong>Phone:</strong></div><div>${user.phone || '-'}</div>
                <div><strong>Date of Birth:</strong></div><div>${user.date_of_birth || '-'}</div>
                <div><strong>Province:</strong></div><div>${user.province || '-'}</div>
                <div><strong>District:</strong></div><div>${user.district || '-'}</div>
                <div><strong>Sector:</strong></div><div>${user.sector || '-'}</div>
                <div><strong>Village:</strong></div><div>${user.village || '-'}</div>
                <div><strong>Gender:</strong></div><div>${user.gender || '-'}</div>
                <div><strong>Marital Status:</strong></div><div>${user.marital_status || '-'}</div>
                <div><strong>Membership Type:</strong></div><div>${user.membership_type || '-'}</div>
                <div><strong>Occupation:</strong></div><div>${user.occupation || '-'}</div>
                <div><strong>Ministry Role:</strong></div><div>${user.role || '-'}</div>
                <div><strong>Status:</strong></div><div>${user.status || '-'}</div>
                <div><strong>Registered:</strong></div><div>${new Date(user.created_at).toLocaleDateString()}</div>
            </div>
        `;
        document.getElementById('detailsContent').innerHTML = details;
        document.getElementById('viewDetailsModal').style.display = 'flex';
    }
    
    function openEditModal(user) {
        document.getElementById('edit_id').value = user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_dob').value = user.date_of_birth || '';
        document.getElementById('edit_province').value = user.province || '';
        document.getElementById('edit_district').value = user.district || '';
        document.getElementById('edit_sector').value = user.sector || '';
        document.getElementById('edit_village').value = user.village || '';
        document.getElementById('edit_gender').value = user.gender || '';
        document.getElementById('edit_marital').value = user.marital_status || '';
        document.getElementById('edit_membership').value = user.membership_type || 'Permanent';
        document.getElementById('edit_occupation').value = user.occupation || '';
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_status').value = user.status;
        document.getElementById('editModal').style.display = 'flex';
    }
    
    function deleteUser(id) {
        if (confirm('Permanently delete this user?')) {
            let form = document.createElement('form'); form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'+id+'">';
            document.body.appendChild(form); form.submit();
        }
    }
    
    function resetUserPassword(id) {
        if (confirm('Reset password? A new temporary password will be generated.')) {
            let form = document.createElement('form'); form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="'+id+'">';
            document.body.appendChild(form); form.submit();
        }
    }
    
    function approveUser(id) {
        if (confirm('Approve this user? They will be able to log in.')) {
            let form = document.createElement('form'); form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="'+id+'">';
            document.body.appendChild(form); form.submit();
        }
    }
    
    function rejectUser(id) {
        if (confirm('Reject this user? The account will be marked as rejected.')) {
            let form = document.createElement('form'); form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="'+id+'">';
            document.body.appendChild(form); form.submit();
        }
    }
    
    function toggleUserStatus(id) {
        if (confirm('Change this user\'s status (activate/deactivate)?')) {
            let form = document.createElement('form'); form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="'+id+'">';
            document.body.appendChild(form); form.submit();
        }
    }
    
    function toggleDropdown(id) {
        const dropdown = document.getElementById('dropdown-' + id);
        if (dropdown) dropdown.classList.toggle('hidden');
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (menu.id !== 'dropdown-' + id) menu.classList.add('hidden');
        });
    }
    
    window.onclick = function(e) {
        if (!e.target.closest('.dropdown-menu') && !e.target.closest('.actions-btn')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));
        }
        if (e.target.classList && e.target.classList.contains('modal')) closeModal(e.target.id);
    }
</script>