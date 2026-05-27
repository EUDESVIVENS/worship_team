<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$activeTab = $_GET['tab'] ?? 'playlist';

// Fetch all necessary data
$songs = $pdo->query("SELECT id, title FROM songs ORDER BY title")->fetchAll();
$playlists = $pdo->query("SELECT * FROM playlists ORDER BY created_at DESC")->fetchAll();
$gallery = $pdo->query("SELECT g.*, u.name as uploader FROM photo_gallery g LEFT JOIN users u ON g.uploaded_by = u.id ORDER BY g.created_at DESC")->fetchAll();
$groups = $pdo->query("SELECT g.*, u.name as leader_name FROM groups_table g LEFT JOIN users u ON g.leader_id = u.id ORDER BY g.created_at DESC")->fetchAll();
$boardPosts = $pdo->query("SELECT p.*, u.name as author FROM public_board_posts p LEFT JOIN users u ON p.posted_by = u.id ORDER BY p.created_at DESC")->fetchAll();
$actionPlans = $pdo->query("SELECT a.*, 
    (SELECT name FROM users WHERE id = a.assigned_to) as assignee_name,
    (SELECT name FROM users WHERE id = a.created_by) as creator_name
    FROM action_plans a ORDER BY due_date ASC, created_at DESC")->fetchAll();
$allUsers = $pdo->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name")->fetchAll();
?>

<style>
     body { font-family: 'Inter', system-ui, sans-serif; width: 100%; font-size: 14px;}
/* Tab styling */
.tab-btn {
    background: transparent;
    color: #4b5563;
    padding: 8px 20px;
    border-radius: 0;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    position: relative;
    border-bottom: 3px solid transparent;
}
.tab-btn i { font-size: 1rem; }
.tab-btn:hover {
    color: #1e3c72;
    background: transparent;
    transform: translateY(-2px);
}
.tab-btn.active {
    background: transparent;
    color: #1e3c72;
    transform: translateY(0);
    border-bottom-color: #1e3c72;
    box-shadow: none;
}
.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 100%;
    height: 3px;
    background: #1e3c72;
    border-radius: 3px;
}

.view-details-btn{
    background: #18709c;
    color:white;
}

.view-details-btn:hover{
    background: #5578ca;
}
.tab-content {
    
    display: none;
    animation: fadeIn 0.3s ease;
}
.tab-content.active-tab { display: block; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.sort-handle { cursor: grab; }
.sort-handle:active { cursor: grabbing; }
.song-select-wrapper { width: 320px; min-width: 320px; }
.data-table td:last-child { white-space: nowrap; }
.data-table td:nth-child(5) { width: 180px; }
.data-table td:nth-child(6) { width: 260px; }
.playlist-song-box { width: 100%; margin-bottom: 20px; }
.playlist-song-form {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: nowrap;
    width: 100%;
}
.song-select-wrapper select { width: 100%; height: 42px; }
.btn-sm {
    height: 42px !important;
    padding: 0 14px !important;
    font-size: 0.82rem;
    border-radius: 8px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.batch-card {
    transition: all 0.2s;
}
.view-details-btn{
    background:#18709c;
    color:white;
    margin-right:10px;
    margin-bottom:10px;
}
.batch-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
</style>
<body>
<div class="mb-6">
    <div class="flex flex-wrap gap-3 border-b border-gray-200 pb-3">
        <button class="tab-btn <?php echo $activeTab == 'playlist' ? 'active' : ''; ?>" data-tab="playlist"><i class="fas fa-music"></i> Playlist</button>
        <button class="tab-btn <?php echo $activeTab == 'gallery' ? 'active' : ''; ?>" data-tab="gallery"><i class="fas fa-images"></i> Photo Gallery</button>
        <button class="tab-btn <?php echo $activeTab == 'groups' ? 'active' : ''; ?>" data-tab="groups"><i class="fas fa-users"></i> Groups</button>
        <button class="tab-btn <?php echo $activeTab == 'board' ? 'active' : ''; ?>" data-tab="board"><i class="fas fa-bullhorn"></i> Public Board</button>
        <button class="tab-btn <?php echo $activeTab == 'actionplan' ? 'active' : ''; ?>" data-tab="actionplan"><i class="fas fa-tasks"></i> Action Plan</button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="success-msg mb-4"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?></div>
<?php endif; ?>

<!-- ======================= TAB 1: PLAYLIST ======================= -->
<div id="tab-playlist" class="tab-content <?php echo $activeTab == 'playlist' ? 'active-tab' : ''; ?>">
    <!-- ... (keep your existing playlist code) ... -->
    <!-- I’ll keep it as you had – if needed, copy from your previous working version -->
    <div class="card"><h2><i class="fas fa-plus-circle"></i> Create New Playlist</h2>
        <form method="POST" action="?page=music_evangelism" class="form-grid">
            <input type="hidden" name="sub" value="playlist"><input type="hidden" name="action" value="add_playlist">
            <div class="form-group"><label>Playlist Title</label><input type="text" name="title" class="form-control" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            <div class="flex items-center" style="margin-top:10px;"><button type="submit" class="btn" style="width:auto;"><i class="fas fa-plus"></i> Create Playlist</button></div>
        </form>
    </div>
    <?php foreach ($playlists as $pl): ?>
        <div class="card">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <h2><i class="fas fa-list-ul"></i> <?php echo htmlspecialchars($pl['title']); ?></h2>
                <div><button onclick="openEditPlaylistModal(<?php echo htmlspecialchars(json_encode($pl)); ?>)" class="edit-btn">Edit</button><button onclick="deleteItem('playlist', <?php echo $pl['id']; ?>)" class="delete-btn">Delete</button></div>
            </div>
            <p class="text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($pl['description'])); ?></p>
            <div class="playlist-song-box">
                <form method="POST" action="?page=music_evangelism" class="playlist-song-form">
                    <input type="hidden" name="sub" value="playlist"><input type="hidden" name="action" value="add_song_to_playlist"><input type="hidden" name="playlist_id" value="<?php echo $pl['id']; ?>">
                    <div class="song-select-wrapper">
                        <select name="song_id" class="form-control modern-select" required>
                            <option value="">-- Search or Select Song --</option>
                            <?php foreach ($songs as $s): ?><option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['title']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm"><i class="fas fa-plus-circle"></i> Add Song</button>
                    <button type="button" onclick="openSongModal()" class="btn btn-sm"><i class="fas fa-music"></i> Create New Song</button>
                </form>
            </div>
            <?php
            $playlistSongs = $pdo->prepare("SELECT ps.id as ps_id, ps.order_index, s.* FROM playlist_songs ps JOIN songs s ON ps.song_id = s.id WHERE ps.playlist_id = ? ORDER BY ps.order_index");
            $playlistSongs->execute([$pl['id']]);
            $songsInPlaylist = $playlistSongs->fetchAll();
            ?>
            <?php if (count($songsInPlaylist) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead><tr><th style="width:120px;">Order</th><th>Song Title</th><th>Key</th><th>Tempo</th><th style="width:180px;">Assigned Singer</th><th style="width:260px;">Actions</th></tr></thead>
                        <tbody id="playlist-sortable-<?php echo $pl['id']; ?>">
                            <?php foreach ($songsInPlaylist as $ps): ?>
                                <tr data-id="<?php echo $ps['ps_id']; ?>">
                                    <td><i class="fas fa-grip-vertical sort-handle"></i></td>
                                    <td><?php echo htmlspecialchars($ps['title']); ?></td>
                                    <td><?php echo htmlspecialchars($ps['song_key']); ?></td>
                                    <td><?php echo htmlspecialchars($ps['tempo']); ?></td>
                                    <td><?php echo htmlspecialchars($ps['assigned_singer']); ?></td>
                                    <td>
                                        <button onclick="viewSongDetails(<?php echo htmlspecialchars(json_encode($ps)); ?>)" class="edit-btn">View Lyrics</button>
                                        <button onclick="editSong(<?php echo htmlspecialchars(json_encode($ps)); ?>)" class="edit-btn">Edit</button>
                                        <button onclick="deleteItem('playlist_song', <?php echo $ps['ps_id']; ?>)" class="delete-btn">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button onclick="savePlaylistOrder(<?php echo $pl['id']; ?>)" class="btn btn-secondary mt-3">Save Order</button>
            <?php else: ?>
                <p class="text-gray-500 italic">No songs in this playlist yet.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- ======================= TAB 2: PHOTO GALLERY ======================= -->
<div id="tab-gallery" class="tab-content <?php echo $activeTab == 'gallery' ? 'active-tab' : ''; ?>">
    <div class="card"><h2><i class="fas fa-upload"></i> Upload Photo</h2>
        <form method="POST" enctype="multipart/form-data" action="?page=music_evangelism" class="form-grid">
            <input type="hidden" name="sub" value="gallery"><input type="hidden" name="action" value="add_photo">
            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            <div class="form-group"><label>Image</label><input type="file" name="image" accept="image/*" class="form-control" required></div>
            <div class="modal-footer"><button type="submit" class="btn">Upload</button></div>
        </form>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($gallery as $g): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <img src="../<?php echo $g['image_path']; ?>" class="w-full h-40 object-cover">
                <div class="p-3">
                    <h3 class="font-bold"><?php echo htmlspecialchars($g['title']); ?></h3>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($g['description']); ?></p>
                    <p class="text-xs text-gray-400 mt-1">By <?php echo htmlspecialchars($g['uploader']); ?></p>
                    <div class="mt-2 flex justify-between">
                        <button onclick="openEditPhotoModal(<?php echo htmlspecialchars(json_encode($g)); ?>)" class="edit-btn">Edit</button>
                        <button onclick="deleteItem('gallery', <?php echo $g['id']; ?>)" class="delete-btn">Delete</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ======================= TAB 3: GROUPS ======================= -->
<div id="tab-groups" class="tab-content <?php echo $activeTab == 'groups' ? 'active-tab' : ''; ?>">
    
    <!-- Action Buttons -->
    <div class="card mb-4">
        <div class="flex flex-wrap gap-3 items-center">
            <button onclick="openSingerSettingsModal()" class="btn"><i class="fas fa-cog"></i> Settings</button>
            <button onclick="viewPreviousTeams()" class="btn"><i class="fas fa-history"></i> View Previous</button>
        
        </div>
    </div>

    <!-- Service Team Generator -->
    <div class="card" id="teamGenForm">
        <h2><i class="fas fa-calendar-alt"></i> Service Team Generator</h2>
        <p class="text-gray-600 mb-4">Automatically generate balanced singer teams for services based on voice part and performance level.</p>
        <form method="POST" action="?page=music_evangelism" class="form-grid mb-6">
            <input type="hidden" name="sub" value="service_team">
            <input type="hidden" name="action" value="generate_teams">
            <div class="form-group"><label>Service Date</label><input type="date" name="service_date" class="form-control" required></div>
            <div class="form-group"><label>Service Name</label><input type="text" name="service_name" class="form-control" placeholder="e.g., Sunday Morning" required></div>
            <div class="form-group"><label>Number of Teams to generate</label><input type="number" name="num_teams" class="form-control" min="1" max="10" value="1" required></div>
            <div class="form-group" style="margin-top: 30px;"><button type="submit" class="btn">Generate Teams</button></div>
        </form>
    </div>

 <!-- Most Recent Batch – All Teams in One Table -->
<div class="card">
    <div class="flex justify-between items-center flex-wrap gap-3 mb-4">
        <h2><i class="fas fa-clock"></i> Most Recent Generated Teams</h2>
        <?php
        $latestBatch = $pdo->query("SELECT batch_id FROM service_teams WHERE batch_id IS NOT NULL ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        if ($latestBatch): ?>
            <button onclick="exportBatch('<?php echo htmlspecialchars($latestBatch); ?>')" class="btn  btn-outline" style="border:1px solid #17a2b8; color:#17a2b8; background:transparent; margin-top:-15px;">
                <i class="fas fa-download"></i> Export
            </button>
        <?php endif; ?>
    </div>
    <!-- rest of the table -->
    <?php
    // Find the most recent batch_id again (re‑fetch to be safe)
    $latestBatch = $pdo->query("SELECT batch_id FROM service_teams WHERE batch_id IS NOT NULL ORDER BY created_at DESC LIMIT 1")->fetchColumn();
    if ($latestBatch):
        $teamsInBatch = $pdo->prepare("SELECT * FROM service_teams WHERE batch_id = ? ORDER BY id");
        $teamsInBatch->execute([$latestBatch]);
        $teams = $teamsInBatch->fetchAll();

        // Collect all members from all teams in this batch
        $allMembers = [];
        foreach ($teams as $team) {
            $members = $pdo->prepare("SELECT u.name, u.voice_part, u.performance_level, ? as team_name FROM service_team_members stm JOIN users u ON stm.user_id = u.id WHERE stm.service_team_id = ?");
            $members->execute([$team['service_name'], $team['id']]);
            while ($row = $members->fetch(PDO::FETCH_ASSOC)) {
                $allMembers[] = $row;
            }
        }
    ?>
        <?php if (count($allMembers) > 0): ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Voice Part</th>
                            <th>Performance Level</th>
                            <th>Team Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allMembers as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                            <td><?php echo htmlspecialchars($member['voice_part']); ?></td>
                            <td><?php echo htmlspecialchars($member['performance_level']); ?></td>
                            <td><?php echo htmlspecialchars($member['team_name']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex flex-wrap gap-6">
                <?php foreach ($teams as $team): ?>
                    <button onclick="viewTeamDetails(<?php echo $team['id']; ?>)" class="btn btn-sm view-details-btn">
                        View Details for <?php echo htmlspecialchars($team['service_name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 italic">No members found in the latest batch.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-gray-500 italic">No service teams generated yet. Use the generator above to create one.</p>
    <?php endif; ?>
</div>

<!-- ======================= TAB 5: ACTION PLAN ======================= -->
<div id="tab-actionplan" class="tab-content <?php echo $activeTab == 'actionplan' ? 'active-tab' : ''; ?>">
    <div class="card"><h2><i class="fas fa-clipboard-list"></i> Create Action Plan</h2>
        <form method="POST" action="?page=music_evangelism" class="form-grid">
            <input type="hidden" name="sub" value="actionplan"><input type="hidden" name="action" value="add_plan">
            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
            <div class="form-group"><label>Assign To</label><select name="assigned_to" class="form-control"><option value="">-- Select Member --</option><?php foreach ($allUsers as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Due Date</label><input type="date" name="due_date" class="form-control"></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
            <div class="form-group"><label>Progress (%)</label><input type="number" name="progress" class="form-control" min="0" max="100" value="0"></div>
            <div class="modal-footer"><button type="submit" class="btn">Create Plan</button></div>
        </form>
    </div>
    <?php foreach ($actionPlans as $plan): ?>
        <div class="card">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <h2><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($plan['title']); ?></h2>
                <div><button onclick="openEditPlanModal(<?php echo htmlspecialchars(json_encode($plan)); ?>)" class="edit-btn">Edit</button><button onclick="deleteItem('actionplan', <?php echo $plan['id']; ?>)" class="delete-btn">Delete</button></div>
            </div>
            <p><?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
            <div class="grid grid-cols-2 gap-2 mt-2 text-sm">
                <div><strong>Assigned to:</strong> <?php echo htmlspecialchars($plan['assignee_name'] ?: 'Not assigned'); ?></div>
                <div><strong>Due:</strong> <?php echo $plan['due_date'] ? date('M d, Y', strtotime($plan['due_date'])) : 'No deadline'; ?></div>
                <div><strong>Status:</strong> <span class="badge"><?php echo ucfirst(str_replace('_',' ',$plan['status'])); ?></span></div>
                <div><strong>Progress:</strong> <div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo $plan['progress']; ?>%;"></div></div> <?php echo $plan['progress']; ?>%</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ======================= MODALS ======================= -->

<!-- Singer Settings Modal (only Manage Singers) -->
<div id="singerSettingsModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">Manage Singers (Voice Part & Level)</div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Voice Part</th><th>Performance Level</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php $singers = $pdo->query("SELECT id, name, email, voice_part, performance_level FROM users WHERE status = 'active' ORDER BY name")->fetchAll(); ?>
                    <?php foreach ($singers as $singer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($singer['name']); ?></td>
                        <td><?php echo htmlspecialchars($singer['email']); ?></td>
                        <td>
                            <select class="form-control singer-voice" data-id="<?php echo $singer['id']; ?>">
                                <option value="">-- None --</option>
                                <option value="Soprano" <?php echo $singer['voice_part'] == 'Soprano' ? 'selected' : ''; ?>>Soprano</option>
                                <option value="Alto" <?php echo $singer['voice_part'] == 'Alto' ? 'selected' : ''; ?>>Alto</option>
                                <option value="Tenor" <?php echo $singer['voice_part'] == 'Tenor' ? 'selected' : ''; ?>>Tenor</option>
                                <option value="Bass" <?php echo $singer['voice_part'] == 'Bass' ? 'selected' : ''; ?>>Bass</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control singer-level" data-id="<?php echo $singer['id']; ?>">
                                <option value="Normal" <?php echo $singer['performance_level'] == 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="Good" <?php echo $singer['performance_level'] == 'Good' ? 'selected' : ''; ?>>Good</option>
                            </select>
                        </td>
                        <td><button class="btn btn-sm save-singer" data-id="<?php echo $singer['id']; ?>">Save</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('singerSettingsModal')">Close</button></div>
    </div>
</div>

<!-- View Previous Teams Modal (prototype style) -->

<!-- View Previous Teams Modal (matching Manage Singers style) -->
<div id="previousTeamsModal" class="modal">
    <div class="modal-content" style="max-width: 950px; background: white; border-radius: 20px; padding: 0; overflow: hidden;">
        <div class="modal-header" style="font-size: 1.25rem; font-weight: bold; border-bottom: 1px solid #eee; padding: 18px 24px;">
            <i class="fas fa-history mr-2"></i> Group Generation History
        </div>
        <div style="max-height: 550px; overflow-y: auto; padding: 20px;">
            <?php
            $allTeams = $pdo->query("SELECT *, DATE_FORMAT(created_at, '%c/%e/%Y, %l:%i:%s %p') as formatted_date FROM service_teams ORDER BY created_at DESC")->fetchAll();
            $batches = [];
            foreach ($allTeams as $team) {
                $batchKey = $team['batch_id'] ?: $team['service_date'] . '_' . preg_replace('/\s*\(Team \d+\)/', '', $team['service_name']);
                if (!isset($batches[$batchKey])) {
                    $batches[$batchKey] = [
                        'created_at' => $team['created_at'],
                        'formatted_date' => $team['formatted_date'],
                        'teams' => []
                    ];
                }
                $batches[$batchKey]['teams'][] = $team;
            }
            ?>
            <?php if (count($batches) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Generated On</th>
                            <th>Teams & Members</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $batchKey => $batch): ?>
                        <tr>
                            <td style="vertical-align: top; white-space: nowrap;">
                                <?php echo htmlspecialchars($batch['formatted_date']); ?>
                            </td>
                            <td style="vertical-align: top;">
                                <?php
                                $teamInfo = [];
                                foreach ($batch['teams'] as $team) {
                                    $count = $pdo->prepare("SELECT COUNT(*) FROM service_team_members WHERE service_team_id = ?");
                                    $count->execute([$team['id']]);
                                    $memberCount = $count->fetchColumn();
                                    $teamInfo[] = $memberCount . ' in ' . $team['service_name'];
                                }
                                echo implode(' • ', array_map('htmlspecialchars', $teamInfo));
                                ?>
                            </td>
                            <td style="vertical-align: top; white-space: nowrap;">
                                <div class="flex gap-2">
                                    <button onclick="restoreBatch('<?php echo htmlspecialchars($batchKey); ?>')" class="edit-btn" style="background: #2a5298; color: white;">
                                        <i class="fas fa-undo-alt"></i> Restore
                                    </button>
                                    <button onclick="exportBatch('<?php echo htmlspecialchars($batchKey); ?>')" class="edit-btn" style="background: #5a8bc7; color: white;">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <button onclick="deleteBatch('<?php echo htmlspecialchars($batchKey); ?>')" class="delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <button onclick="viewBatchDetails('<?php echo htmlspecialchars($batchKey); ?>')" class="edit-btn" style="background: #ffc107; color: #333;">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                    <p>No previous team generations found.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer" style="background: #f9fafb; padding: 12px 20px; border-top: 1px solid #e5e7eb;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('previousTeamsModal')">Close</button>
        </div>
    </div>
</div>
<!-- View Generated List Modal -->
<div id="generatedListModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">All Generated Teams</div>
        <div id="generatedListContent">
            <?php
            $allTeamsSimple = $pdo->query("SELECT id, service_name, service_date FROM service_teams ORDER BY service_date DESC, created_at DESC")->fetchAll();
            if (count($allTeamsSimple) > 0): ?>
                <table class="data-table"><thead><tr><th>Date</th><th>Service Name</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($allTeamsSimple as $at): ?>
                    <tr><td><?php echo htmlspecialchars($at['service_date']); ?></td><td><?php echo htmlspecialchars($at['service_name']); ?></td><td><button onclick="viewTeamDetails(<?php echo $at['id']; ?>)" class="btn btn-sm">View Team</button></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php else: ?>
                <p class="text-gray-500 italic">No generated teams yet.</p>
            <?php endif; ?>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('generatedListModal')">Close</button></div>
    </div>
</div>

<!-- Team Details Modal -->
<div id="teamDetailsModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">Team Details</div>
        <div id="teamDetailsContent"></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('teamDetailsModal')">Close</button></div>
    </div>
</div>
</body>
<!-- Edit Playlist Modal, Edit Photo Modal, Edit Group Modal, Edit Board Modal, Edit Plan Modal, Song Modal (keep as you already have) -->
<!-- ... I'm omitting them for brevity, but they are exactly as in your original file. You can copy them from your previous working version. -->

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
// ========== General Functions ==========
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function escapeHtml(str) { if (!str) return ''; return str.replace(/[&<>]/g, function(m) { if (m === '&') return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') return '&gt;'; return m; }); }

// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(pane => pane.classList.remove('active-tab'));
        document.getElementById('tab-' + tab).classList.add('active-tab');
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.pushState({}, '', url);
    });
});

// Delete helper (for generic items)
function deleteItem(type, id) {
    if (!confirm('Are you sure you want to delete this item?')) return;
    let form = document.createElement('form'); form.method = 'POST';
    let sub = '', action = '';
    switch(type) {
        case 'playlist': sub='playlist'; action='delete_playlist'; break;
        case 'playlist_song': sub='playlist'; action='remove_song_from_playlist'; break;
        case 'gallery': sub='gallery'; action='delete_photo'; break;
        case 'group': sub='group'; action='delete_group'; break;
        case 'board': sub='board'; action='delete_post'; break;
        case 'actionplan': sub='actionplan'; action='delete_plan'; break;
        default: return;
    }
    form.innerHTML = `<input type="hidden" name="sub" value="${sub}"><input type="hidden" name="action" value="${action}"><input type="hidden" name="id" value="${id}">`;
    document.body.appendChild(form); form.submit();
}

// Playlist sort
function savePlaylistOrder(playlist_id) {
    const rows = document.querySelectorAll(`#playlist-sortable-${playlist_id} tr`);
    const order = Array.from(rows).map(row => row.getAttribute('data-id'));
    let form = document.createElement('form'); form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="sub" value="playlist"><input type="hidden" name="action" value="reorder_playlist"><input type="hidden" name="playlist_id" value="${playlist_id}"><input type="hidden" name="order" value='${JSON.stringify(order)}'>`;
    document.body.appendChild(form); form.submit();
}

// Song modal (existing)
function openSongModal(song) { /* your existing code */ }
function editSong(song) { openSongModal(song); }
function viewSongDetails(song) { /* your existing code */ }

// Existing modal open functions (edit playlist, photo, group, board, plan) - keep your existing ones
function openEditPlaylistModal(data) { /* existing */ }
function openEditPhotoModal(data) { /* existing */ }
function openEditGroupModal(data) { /* existing */ }
function openEditBoardModal(data) { /* existing */ }
function openEditPlanModal(data) { /* existing */ }

// ========== Groups Tab Specific ==========
function openSingerSettingsModal() { document.getElementById('singerSettingsModal').style.display = 'flex'; }
function viewPreviousTeams() { document.getElementById('previousTeamsModal').style.display = 'flex'; }
function viewGeneratedList() { document.getElementById('generatedListModal').style.display = 'flex'; }
function scrollToGenerator() { document.getElementById('teamGenForm').scrollIntoView({ behavior: 'smooth' }); }

// Save singer voice/level (AJAX)
document.querySelectorAll('.save-singer').forEach(btn => {
    btn.addEventListener('click', function() {
        const userId = this.getAttribute('data-id');
        const voiceSelect = document.querySelector(`.singer-voice[data-id="${userId}"]`);
        const levelSelect = document.querySelector(`.singer-level[data-id="${userId}"]`);
        const voice = voiceSelect.value;
        const level = levelSelect.value;
        fetch('ajax_update_singer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}&voice_part=${voice}&performance_level=${level}`
        })
        .then(response => response.json())
        .then(data => { if (data.success) alert('Updated successfully'); else alert('Error: ' + data.error); })
        .catch(err => alert('Request failed.'));
    });
});

// Delete Service Team (single team)
function deleteServiceTeam(id) {
    if (confirm('Delete this service team assignment?')) {
        let form = document.createElement('form'); form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="sub" value="service_team"><input type="hidden" name="action" value="delete_service_team"><input type="hidden" name="id" value="'+id+'">';
        document.body.appendChild(form); form.submit();
    }
}

// Batch actions
function restoreBatch(batchKey) {
    if (confirm('Restore this generation? The team configuration will be copied to the generator form.')) {
        window.location.href = `?page=music_evangelism&tab=groups&restore_batch=${batchKey}`;
    }
}
function exportBatch(batchKey) {
    window.location.href = `export_batch.php?batch_id=${batchKey}`;
}
function deleteBatch(batchKey) {
    if (confirm('Delete this entire generation batch? This action cannot be undone.')) {
        let form = document.createElement('form'); form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="sub" value="service_team"><input type="hidden" name="action" value="delete_batch"><input type="hidden" name="batch_id" value="'+batchKey+'">';
        document.body.appendChild(form); form.submit();
    }
}
function viewBatchDetails(batchKey) {
    fetch(`ajax_get_batch_details.php?batch_id=${batchKey}`)
        .then(response => response.json())
        .then(data => {
            let html = '<table class="data-table"><thead><tr><th>Name</th><th>Voice Part</th><th>Level</th><th>Team</th></tr></thead><tbody>';
            data.forEach(member => {
                html += `<tr><td>${escapeHtml(member.name)}</td><td>${escapeHtml(member.voice_part)}</td><td>${escapeHtml(member.performance_level)}</td><td>${escapeHtml(member.team_name)}</td></tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('teamDetailsContent').innerHTML = html;
            document.getElementById('teamDetailsModal').style.display = 'flex';
        });
}
function viewTeamDetails(teamId) {
    fetch(`ajax_get_team_details.php?team_id=${teamId}`)
        .then(response => response.json())
        .then(data => {
            let html = '<table class="data-table"><thead><tr><th>Name</th><th>Voice Part</th><th>Level</th></tr></thead><tbody>';
            data.forEach(member => {
                html += `<tr><td>${escapeHtml(member.name)}</td><td>${escapeHtml(member.voice_part)}</td><td>${escapeHtml(member.performance_level)}</td></tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('teamDetailsContent').innerHTML = html;
            document.getElementById('teamDetailsModal').style.display = 'flex';
        });
}

// Initialize Sortable for each playlist
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($playlists as $pl): ?>
        new Sortable(document.getElementById('playlist-sortable-<?php echo $pl['id']; ?>'), { animation: 150, handle: '.sort-handle' });
    <?php endforeach; ?>
});
</script>