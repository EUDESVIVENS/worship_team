<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$activeTab = $_GET['tab'] ?? 'playlist';

// Fetch songs
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
.tab-btn i {
    font-size: 1rem;
}
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
.tab-content {
    display: none;
    animation: fadeIn 0.3s ease;
}
.tab-content.active-tab {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
/* Other existing styles */
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
</style>

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
    <!-- ... (playlist content same as before, not changed) ... -->
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
                <div>
                    <button onclick="openEditPlaylistModal(<?php echo htmlspecialchars(json_encode($pl)); ?>)" class="edit-btn"><i class="fas fa-edit"></i> Edit</button>
                    <button onclick="deleteItem('playlist', <?php echo $pl['id']; ?>)" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
                </div>
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
    <!-- ... (unchanged, but we keep it concise) ... -->
    <div class="card"><h2><i class="fas fa-upload"></i> Upload Photo</h2>
        <form method="POST" enctype="multipart/form-data" action="?page=music_evangelism" class="form-grid">
            <input type="hidden" name="sub" value="gallery"><input type="hidden" name="action" value="add_photo">
            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            <div class="form-group"><label>Image</label><input type="file" name="image" accept="image/*" class="form-control" required></div>
            <div class="modal-footer" style="margin-top:0;"><button type="submit" class="btn">Upload</button></div>
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
    <!-- List existing singing groups -->
    <?php foreach ($groups as $g): ?>
        <div class="card">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <h2><i class="fas fa-users"></i> <?php echo htmlspecialchars($g['name']); ?></h2>
                <div>
                    <button onclick="openEditGroupModal(<?php echo htmlspecialchars(json_encode($g)); ?>)" class="edit-btn">Edit</button>
                    <button onclick="deleteItem('group', <?php echo $g['id']; ?>)" class="delete-btn">Delete</button>
                </div>
            </div>
            <p><?php echo nl2br(htmlspecialchars($g['description'])); ?></p>
            <p><strong>Leader:</strong> <?php echo htmlspecialchars($g['leader_name'] ?: 'Not assigned'); ?></p>
            <p><strong>Services:</strong> <?php echo htmlspecialchars($g['services']); ?></p>
            <?php
            $members = $pdo->prepare("SELECT u.name FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ?");
            $members->execute([$g['id']]);
            $memberList = $members->fetchAll();
            ?>
            <p><strong>Members:</strong> <?php echo implode(', ', array_column($memberList, 'name')) ?: 'None'; ?></p>
        </div>
    <?php endforeach; ?>

    <!-- MANAGE SINGERS -->
    <div class="card">
        <h2><i class="fas fa-microphone-alt"></i> Manage Singers (Voice Part & Level)</h2>
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
    </div>

    <!-- SERVICE TEAM GENERATOR -->
    <div class="card">
        <h2><i class="fas fa-calendar-alt"></i> Service Team Generator</h2>
        <p class="text-gray-600 mb-4">Automatically generate balanced singer teams for services based on voice part and performance level.</p>
        <form method="POST" action="?page=music_evangelism" class="form-grid mb-6" id="teamGenForm">
            <input type="hidden" name="sub" value="service_team">
            <input type="hidden" name="action" value="generate_teams">
            <div class="form-group"><label>Service Date</label><input type="date" name="service_date" class="form-control" required></div>
            <div class="form-group"><label>Service Name</label><input type="text" name="service_name" class="form-control" placeholder="e.g., Sunday Morning" required></div>
            <div class="form-group"><label>Number of Teams to generate</label><input type="number" name="num_teams" class="form-control" min="1" max="10" value="1" required></div>
            <!-- Restored required singers per voice part -->
            <div class="form-group">
                <label>Required singers per voice part (per team)</label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" name="required_soprano" placeholder="Soprano" class="form-control" value="2">
                    <input type="number" name="required_alto" placeholder="Alto" class="form-control" value="2">
                    <input type="number" name="required_tenor" placeholder="Tenor" class="form-control" value="1">
                    <input type="number" name="required_bass" placeholder="Bass" class="form-control" value="1">
                </div>
            </div>
            <div class="form-group">
                <label>Avoid pairing same singers repeatedly (rotation)</label>
                <select name="rotation_mode" class="form-control">
                    <option value="simple">Simple random</option>
                    <option value="balanced" selected>Balanced rotation (prevents repeats)</option>
                </select>
            </div>
            <div class="modal-footer" style="margin-top:0;"><button type="submit" class="btn">Generate Teams</button></div>
        </form>

        <h3>Generated Service Teams</h3>
        <?php $serviceTeams = $pdo->query("SELECT * FROM service_teams ORDER BY service_date DESC, created_at DESC")->fetchAll(); ?>
        <?php if (count($serviceTeams) > 0): ?>
            <?php foreach ($serviceTeams as $team): ?>
                <?php $members = $pdo->prepare("SELECT u.name, u.voice_part, u.performance_level FROM service_team_members stm JOIN users u ON stm.user_id = u.id WHERE stm.service_team_id = ? ORDER BY u.voice_part, u.performance_level DESC"); ?>
                <?php $members->execute([$team['id']]); $memberList = $members->fetchAll(); ?>
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="flex justify-between items-center">
                        <h4><?php echo htmlspecialchars($team['service_name']); ?> – <?php echo date('M d, Y', strtotime($team['service_date'])); ?></h4>
                        <button onclick="deleteServiceTeam(<?php echo $team['id']; ?>)" class="delete-btn">Delete Team</button>
                    </div>
                    <table class="data-table mt-2">
                        <thead><tr><th>Name</th><th>Voice Part</th><th>Level</th></tr></thead>
                        <tbody>
                            <?php foreach ($memberList as $m): ?>
                            <tr><td><?php echo htmlspecialchars($m['name']); ?></td><td><?php echo htmlspecialchars($m['voice_part']); ?></td><td><?php echo htmlspecialchars($m['performance_level']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500 italic">No service teams generated yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ======================= TAB 4: PUBLIC BOARD ======================= -->
<div id="tab-board" class="tab-content <?php echo $activeTab == 'board' ? 'active-tab' : ''; ?>">
    <!-- ... (keep same as original, omitted for brevity) ... -->
    <div class="card"><h2><i class="fas fa-bullhorn"></i> Post to Public Board</h2>
        <form method="POST" enctype="multipart/form-data" action="?page=music_evangelism" class="form-grid">
            <input type="hidden" name="sub" value="board"><input type="hidden" name="action" value="add_post">
            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div>
            <div class="form-group"><label>Content</label><textarea name="content" class="form-control" rows="4" required></textarea></div>
            <div class="form-group"><label>Type</label><select name="type" class="form-control"><option value="music">Music Announcement</option><option value="event">Event</option></select></div>
            <div class="form-group"><label>Event Date</label><input type="date" name="event_date" class="form-control"></div>
            <div class="form-group"><label>Image (optional)</label><input type="file" name="image" accept="image/*" class="form-control"></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="published">Published</option><option value="draft">Draft</option></select></div>
            <div class="modal-footer"><button type="submit" class="btn">Publish Post</button></div>
        </form>
    </div>
    <?php foreach ($boardPosts as $post): ?>
        <div class="card">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <h2><i class="fas fa-newspaper"></i> <?php echo htmlspecialchars($post['title']); ?></h2>
                <div><button onclick="openEditBoardModal(<?php echo htmlspecialchars(json_encode($post)); ?>)" class="edit-btn">Edit</button><button onclick="deleteItem('board', <?php echo $post['id']; ?>)" class="delete-btn">Delete</button></div>
            </div>
            <p class="text-gray-500 text-sm">By <?php echo htmlspecialchars($post['author']); ?> on <?php echo date('M d, Y', strtotime($post['created_at'])); ?></p>
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
            <?php if ($post['image_path']): ?><img src="../<?php echo $post['image_path']; ?>" class="mt-2 max-h-48 rounded object-cover"><?php endif; ?>
            <div class="mt-2"><span class="badge"><?php echo ucfirst($post['type']); ?></span><span class="badge <?php echo $post['status']=='published'?'bg-green-100':'bg-gray-100'; ?>"><?php echo ucfirst($post['status']); ?></span></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ======================= TAB 5: ACTION PLAN ======================= -->
<div id="tab-actionplan" class="tab-content <?php echo $activeTab == 'actionplan' ? 'active-tab' : ''; ?>">
    <!-- ... (keep same) ... -->
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
<!-- Edit Playlist Modal, Edit Photo Modal, Edit Group Modal, Edit Board Modal, Edit Plan Modal – same as before – omitted for brevity but must be included. They are already in your file. -->
<!-- We'll add the missing Song Modal and lyricsModal here -->
<div id="songModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header" id="songModalTitle">Add New Song</div>
        <form method="POST" id="songForm">
            <input type="hidden" name="sub" value="playlist">
            <input type="hidden" name="action" id="song_action" value="add_song">
            <input type="hidden" name="id" id="song_id">
            <div class="form-group"><label>Song Title *</label><input type="text" name="title" id="song_title" class="form-control" required></div>
            <div class="row" style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;"><label>Key</label><input type="text" name="song_key" id="song_key" class="form-control" placeholder="e.g., G"></div>
                <div class="form-group" style="flex:1;"><label>Tempo (BPM)</label><input type="number" name="tempo" id="song_tempo" class="form-control" placeholder="72"></div>
            </div>
            <div class="form-group"><label>Lyrics</label><textarea name="lyrics" id="song_lyrics" class="form-control" rows="6" placeholder="Enter lyrics..."></textarea></div>
            <div class="form-group"><label>Music Note</label><textarea name="music_note" id="song_music_note" class="form-control" rows="3" placeholder="Chords or link"></textarea></div>
            <div class="form-group"><label>Assigned Singer</label><input type="text" name="assigned_singer" id="song_singer" class="form-control" placeholder="Main vocalist(s)"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('songModal')">Cancel</button>
                <button type="submit" class="btn">Save Song</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
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

// Delete helper
function deleteItem(type, id) {
    if (!confirm('Are you sure you want to delete this item?')) return;
    let form = document.createElement('form');
    form.method = 'POST';
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
    document.body.appendChild(form);
    form.submit();
}

function savePlaylistOrder(playlist_id) {
    const rows = document.querySelectorAll(`#playlist-sortable-${playlist_id} tr`);
    const order = Array.from(rows).map(row => row.getAttribute('data-id'));
    let form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="sub" value="playlist"><input type="hidden" name="action" value="reorder_playlist"><input type="hidden" name="playlist_id" value="${playlist_id}"><input type="hidden" name="order" value='${JSON.stringify(order)}'>`;
    document.body.appendChild(form);
    form.submit();
}

// Song modal
function openSongModal(song = null) {
    const modal = document.getElementById('songModal');
    const titleElem = document.getElementById('songModalTitle');
    const actionElem = document.getElementById('song_action');
    const idElem = document.getElementById('song_id');
    const nameElem = document.getElementById('song_title');
    const keyElem = document.getElementById('song_key');
    const tempoElem = document.getElementById('song_tempo');
    const lyricsElem = document.getElementById('song_lyrics');
    const noteElem = document.getElementById('song_music_note');
    const singerElem = document.getElementById('song_singer');
    if (song) {
        titleElem.innerText = 'Edit Song';
        actionElem.value = 'edit_song';
        idElem.value = song.id;
        nameElem.value = song.title;
        keyElem.value = song.song_key || '';
        tempoElem.value = song.tempo || '';
        lyricsElem.value = song.lyrics || '';
        noteElem.value = song.music_note || '';
        singerElem.value = song.assigned_singer || '';
    } else {
        titleElem.innerText = 'Add New Song';
        actionElem.value = 'add_song';
        idElem.value = '';
        nameElem.value = '';
        keyElem.value = '';
        tempoElem.value = '';
        lyricsElem.value = '';
        noteElem.value = '';
        singerElem.value = '';
    }
    modal.style.display = 'flex';
}
function editSong(song) { openSongModal(song); }

function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Modals (existing)
function openEditPlaylistModal(data) {
    document.getElementById('edit_playlist_id').value = data.id;
    document.getElementById('edit_playlist_title').value = data.title;
    document.getElementById('edit_playlist_desc').value = data.description;
    document.getElementById('editPlaylistModal').style.display = 'flex';
}
function openEditPhotoModal(data) {
    document.getElementById('edit_photo_id').value = data.id;
    document.getElementById('edit_photo_title').value = data.title;
    document.getElementById('edit_photo_desc').value = data.description;
    document.getElementById('editPhotoModal').style.display = 'flex';
}
function openEditGroupModal(data) {
    document.getElementById('edit_group_id').value = data.id;
    document.getElementById('edit_group_name').value = data.name;
    document.getElementById('edit_group_desc').value = data.description;
    document.getElementById('edit_group_leader').value = data.leader_id || '';
    document.getElementById('edit_group_services').value = data.services;
    document.getElementById('editGroupModal').style.display = 'flex';
}
function openEditBoardModal(data) {
    document.getElementById('edit_board_id').value = data.id;
    document.getElementById('edit_board_title').value = data.title;
    document.getElementById('edit_board_content').value = data.content;
    document.getElementById('edit_board_type').value = data.type;
    document.getElementById('edit_board_date').value = data.event_date || '';
    document.getElementById('edit_board_status').value = data.status;
    document.getElementById('editBoardModal').style.display = 'flex';
}
function openEditPlanModal(data) {
    document.getElementById('edit_plan_id').value = data.id;
    document.getElementById('edit_plan_title').value = data.title;
    document.getElementById('edit_plan_desc').value = data.description;
    document.getElementById('edit_plan_assigned').value = data.assigned_to || '';
    document.getElementById('edit_plan_duedate').value = data.due_date || '';
    document.getElementById('edit_plan_status').value = data.status;
    document.getElementById('edit_plan_progress').value = data.progress;
    document.getElementById('editPlanModal').style.display = 'flex';
}

// View lyrics modal
function viewSongDetails(song) {
    let content = `<div style="max-height: 500px; overflow-y: auto;">
        <h3>${escapeHtml(song.title)}</h3>
        <p><strong>Key:</strong> ${song.song_key || '-'}</p>
        <p><strong>Tempo:</strong> ${song.tempo || '-'} BPM</p>
        <p><strong>Assigned Singer:</strong> ${song.assigned_singer || '-'}</p>
        <hr><strong>Lyrics:</strong><div style="white-space: pre-wrap; background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 10px;">${escapeHtml(song.lyrics) || 'No lyrics provided.'}</div>
        <hr><strong>Music Note:</strong><div style="white-space: pre-wrap; background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 10px;">${escapeHtml(song.music_note) || 'No music note provided.'}</div>
    </div>`;
    let modal = document.getElementById('lyricsModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'lyricsModal';
        modal.className = 'modal';
        modal.innerHTML = `<div class="modal-content" style="max-width: 700px;"><div class="modal-header">Song Details</div><div id="lyricsModalContent"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('lyricsModal')">Close</button></div></div>`;
        document.body.appendChild(modal);
    }
    document.getElementById('lyricsModalContent').innerHTML = content;
    modal.style.display = 'flex';
}
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) { if (m === '&') return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') return '&gt;'; return m; });
}

// Save singer voice part and level (AJAX)
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
        .catch(err => alert('Request failed. Please ensure ajax_update_singer.php exists.'));
    });
});

// Delete Service Team
function deleteServiceTeam(id) {
    if (confirm('Delete this service team assignment?')) {
        let form = document.createElement('form'); form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="sub" value="service_team"><input type="hidden" name="action" value="delete_service_team"><input type="hidden" name="id" value="'+id+'">';
        document.body.appendChild(form); form.submit();
    }
}

// Initialize Sortable
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($playlists as $pl): ?>
        new Sortable(document.getElementById('playlist-sortable-<?php echo $pl['id']; ?>'), { animation: 150, handle: '.sort-handle' });
    <?php endforeach; ?>
});
</script>