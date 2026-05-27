<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';
$tab = $_GET['tab'] ?? 'playlist';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sub = $_POST['sub'] ?? '';

    // ----- PLAYLIST -----
    if ($sub == 'playlist') {
        if ($action == 'add_playlist') {
            $title = trim($_POST['title']);
            $desc = trim($_POST['description']);
            $pdo->prepare("INSERT INTO playlists (title, description) VALUES (?, ?)")->execute([$title, $desc]);
            $message = "Playlist created.";
        } elseif ($action == 'edit_playlist') {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $desc = trim($_POST['description']);
            $pdo->prepare("UPDATE playlists SET title=?, description=? WHERE id=?")->execute([$title, $desc, $id]);
            $message = "Playlist updated.";
        } elseif ($action == 'delete_playlist') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM playlists WHERE id=?")->execute([$id]);
            $message = "Playlist deleted.";
        } elseif ($action == 'add_song_to_playlist') {
            $playlist_id = (int)$_POST['playlist_id'];
            $song_id = (int)$_POST['song_id'];
            $max = $pdo->prepare("SELECT MAX(order_index) FROM playlist_songs WHERE playlist_id=?");
            $max->execute([$playlist_id]);
            $order = $max->fetchColumn() + 1;
            $pdo->prepare("INSERT INTO playlist_songs (playlist_id, song_id, order_index) VALUES (?, ?, ?)")->execute([$playlist_id, $song_id, $order]);
            $message = "Song added to playlist.";
        } elseif ($action == 'remove_song_from_playlist') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM playlist_songs WHERE id=?")->execute([$id]);
            $message = "Song removed.";
        } elseif ($action == 'reorder_playlist') {
            $playlist_id = (int)$_POST['playlist_id'];
            $order = json_decode($_POST['order'], true);
            foreach ($order as $idx => $ps_id) {
                $pdo->prepare("UPDATE playlist_songs SET order_index = ? WHERE id = ? AND playlist_id = ?")->execute([$idx, $ps_id, $playlist_id]);
            }
            $message = "Playlist reordered.";
        } elseif ($action == 'add_song') {
            $title = trim($_POST['title']);
            $song_key = trim($_POST['song_key']);
            $tempo = !empty($_POST['tempo']) ? (int)$_POST['tempo'] : null;
            $lyrics = trim($_POST['lyrics']);
            $music_note = trim($_POST['music_note']);
            $assigned_singer = trim($_POST['assigned_singer']);
            $pdo->prepare("INSERT INTO songs (title, song_key, tempo, lyrics, music_note, assigned_singer) VALUES (?, ?, ?, ?, ?, ?)")->execute([$title, $song_key, $tempo, $lyrics, $music_note, $assigned_singer]);
            $message = "Song added successfully.";
        } elseif ($action == 'edit_song') {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $song_key = trim($_POST['song_key']);
            $tempo = !empty($_POST['tempo']) ? (int)$_POST['tempo'] : null;
            $lyrics = trim($_POST['lyrics']);
            $music_note = trim($_POST['music_note']);
            $assigned_singer = trim($_POST['assigned_singer']);
            $pdo->prepare("UPDATE songs SET title=?, song_key=?, tempo=?, lyrics=?, music_note=?, assigned_singer=? WHERE id=?")->execute([$title, $song_key, $tempo, $lyrics, $music_note, $assigned_singer, $id]);
            $message = "Song updated.";
        } elseif ($action == 'delete_song') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM playlist_songs WHERE song_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM songs WHERE id=?")->execute([$id]);
            $message = "Song deleted.";
        }
    }

    // ----- PHOTO GALLERY -----
    elseif ($sub == 'gallery') {
        if ($action == 'add_photo') {
            $title = trim($_POST['title']);
            $desc = trim($_POST['description']);
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $uploadDir = '../uploads/gallery/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $filename = time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/gallery/' . $filename;
                }
            }
            if ($imagePath) {
                $pdo->prepare("INSERT INTO photo_gallery (title, description, image_path, uploaded_by) VALUES (?, ?, ?, ?)")->execute([$title, $desc, $imagePath, $_SESSION['user_id']]);
                $message = "Photo uploaded.";
            } else {
                $error = "Image upload failed.";
            }
        } elseif ($action == 'edit_photo') {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $desc = trim($_POST['description']);
            $pdo->prepare("UPDATE photo_gallery SET title=?, description=? WHERE id=?")->execute([$title, $desc, $id]);
            $message = "Photo updated.";
        } elseif ($action == 'delete_photo') {
            $id = (int)$_POST['id'];
            $img = $pdo->prepare("SELECT image_path FROM photo_gallery WHERE id=?")->execute([$id])->fetchColumn();
            if ($img && file_exists('../' . $img)) unlink('../' . $img);
            $pdo->prepare("DELETE FROM photo_gallery WHERE id=?")->execute([$id]);
            $message = "Photo deleted.";
        }
    }

    // ----- GROUPS (manually created groups) -----
    elseif ($sub == 'group') {
        if ($action == 'add_group') {
            $name = trim($_POST['name']);
            $desc = trim($_POST['description']);
            $leader_id = !empty($_POST['leader_id']) ? (int)$_POST['leader_id'] : null;
            $services = trim($_POST['services']);
            $pdo->prepare("INSERT INTO groups_table (name, description, leader_id, services) VALUES (?, ?, ?, ?)")->execute([$name, $desc, $leader_id, $services]);
            $groupId = $pdo->lastInsertId();
            if (isset($_POST['members']) && is_array($_POST['members'])) {
                foreach ($_POST['members'] as $uid) {
                    $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)")->execute([$groupId, $uid]);
                }
            }
            $message = "Group created.";
            $tab = 'groups';
        } elseif ($action == 'edit_group') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $desc = trim($_POST['description']);
            $leader_id = !empty($_POST['leader_id']) ? (int)$_POST['leader_id'] : null;
            $services = trim($_POST['services']);
            $pdo->prepare("UPDATE groups_table SET name=?, description=?, leader_id=?, services=? WHERE id=?")->execute([$name, $desc, $leader_id, $services, $id]);
            $pdo->prepare("DELETE FROM group_members WHERE group_id=?")->execute([$id]);
            if (isset($_POST['members']) && is_array($_POST['members'])) {
                foreach ($_POST['members'] as $uid) {
                    $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)")->execute([$id, $uid]);
                }
            }
            $message = "Group updated.";
            $tab = 'groups';
        } elseif ($action == 'delete_group') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM groups_table WHERE id=?")->execute([$id]);
            $message = "Group deleted.";
            $tab = 'groups';
        }
    }

    // ----- PUBLIC BOARD -----
    elseif ($sub == 'board') {
        if ($action == 'add_post') {
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $type = $_POST['type'];
            $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
            $status = $_POST['status'];
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $uploadDir = '../uploads/board/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $filename = time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/board/' . $filename;
                }
            }
            $pdo->prepare("INSERT INTO public_board_posts (title, content, type, image_path, event_date, status, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$title, $content, $type, $imagePath, $event_date, $status, $_SESSION['user_id']]);
            $message = "Post published.";
        } elseif ($action == 'edit_post') {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $type = $_POST['type'];
            $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
            $status = $_POST['status'];
            $pdo->prepare("UPDATE public_board_posts SET title=?, content=?, type=?, event_date=?, status=? WHERE id=?")->execute([$title, $content, $type, $event_date, $status, $id]);
            $message = "Post updated.";
        } elseif ($action == 'delete_post') {
            $id = (int)$_POST['id'];
            $img = $pdo->prepare("SELECT image_path FROM public_board_posts WHERE id=?")->execute([$id])->fetchColumn();
            if ($img && file_exists('../' . $img)) unlink('../' . $img);
            $pdo->prepare("DELETE FROM public_board_posts WHERE id=?")->execute([$id]);
            $message = "Post deleted.";
        }
    }

    // ----- ACTION PLAN -----
    elseif ($sub == 'actionplan') {
        if ($action == 'add_plan') {
            $title = trim($_POST['title']);
            $desc = trim($_POST['description']);
            $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            $status = $_POST['status'];
            $progress = (int)$_POST['progress'];
            $pdo->prepare("INSERT INTO action_plans (title, description, assigned_to, due_date, status, progress, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$title, $desc, $assigned_to, $due_date, $status, $progress, $_SESSION['user_id']]);
            $message = "Action plan created.";
        } elseif ($action == 'edit_plan') {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $desc = trim($_POST['description']);
            $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            $status = $_POST['status'];
            $progress = (int)$_POST['progress'];
            $pdo->prepare("UPDATE action_plans SET title=?, description=?, assigned_to=?, due_date=?, status=?, progress=? WHERE id=?")->execute([$title, $desc, $assigned_to, $due_date, $status, $progress, $id]);
            $message = "Action plan updated.";
        } elseif ($action == 'delete_plan') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM action_plans WHERE id=?")->execute([$id]);
            $message = "Action plan deleted.";
        }
    }

    // ----- SERVICE TEAM (generator, delete, batch) -----
    elseif ($sub == 'service_team') {
        if ($action == 'generate_teams') {
            $service_date = $_POST['service_date'];
            $service_name = trim($_POST['service_name']);
            $num_teams = (int)$_POST['num_teams'];

            // Fetch all active singers with voice part and level
            $singers = $pdo->query("SELECT id, name, voice_part, performance_level FROM users WHERE status = 'active' AND voice_part IS NOT NULL ORDER BY voice_part, performance_level DESC")->fetchAll();
            if (count($singers) == 0) {
                $error = "No singers with assigned voice parts. Please assign voice parts in Settings first.";
                header("Location: ?page=music_evangelism&tab=groups&error=" . urlencode($error));
                exit;
            }

            // Group by voice part and level
            $pools = [];
            foreach (['Soprano', 'Alto', 'Tenor', 'Bass'] as $vp) {
                $pools[$vp]['Good'] = [];
                $pools[$vp]['Normal'] = [];
            }
            foreach ($singers as $s) {
                if (isset($pools[$s['voice_part']])) {
                    $pools[$s['voice_part']][$s['performance_level']][] = $s;
                }
            }

            // Calculate total available per voice part
            $available = [];
            foreach ($pools as $vp => $levels) {
                $available[$vp] = count($levels['Good']) + count($levels['Normal']);
            }

            // Determine required singers per voice part per team (even distribution)
            $required = [];
            foreach ($available as $vp => $total) {
                if ($total == 0) {
                    for ($t = 0; $t < $num_teams; $t++) $required[$vp][$t] = 0;
                    continue;
                }
                $perTeam = floor($total / $num_teams);
                $remainder = $total % $num_teams;
                for ($t = 0; $t < $num_teams; $t++) {
                    $required[$vp][$t] = $perTeam + ($t < $remainder ? 1 : 0);
                }
            }

            // Shuffle pools
            foreach ($pools as $vp => $levels) {
                foreach ($levels as $level => $list) {
                    shuffle($list);
                    $pools[$vp][$level] = $list;
                }
            }

            // Pointers for round-robin selection
            $pointers = [];
            foreach ($pools as $vp => $levels) {
                foreach ($levels as $level => $list) {
                    $pointers[$vp][$level] = 0;
                }
            }

            // Build teams
            $teams = [];
            for ($t = 0; $t < $num_teams; $t++) {
                $teams[$t] = [];
                foreach (['Soprano', 'Alto', 'Tenor', 'Bass'] as $vp) {
                    $teams[$t][$vp] = [];
                    $needed = $required[$vp][$t];
                    // First Good, then Normal
                    foreach (['Good', 'Normal'] as $level) {
                        $pool = $pools[$vp][$level];
                        $poolSize = count($pool);
                        if ($poolSize == 0) continue;
                        while ($needed > 0 && count($teams[$t][$vp]) < $required[$vp][$t]) {
                            $idx = $pointers[$vp][$level] % $poolSize;
                            $singer = $pool[$idx];
                            // Avoid assigning same singer to multiple teams in this batch
                            $alreadyAssigned = false;
                            for ($prev = 0; $prev < $t; $prev++) {
                                if (in_array($singer['id'], array_column($teams[$prev][$vp], 'id'))) {
                                    $alreadyAssigned = true;
                                    break;
                                }
                            }
                            if (!$alreadyAssigned) {
                                $teams[$t][$vp][] = $singer;
                                $needed--;
                            }
                            $pointers[$vp][$level]++;
                            if ($pointers[$vp][$level] >= $poolSize * 2) break; // prevent infinite loop
                        }
                    }
                }
            }

            // Insert into database with batch_id
            $batch_id = uniqid('batch_');
            for ($t = 0; $t < $num_teams; $t++) {
                $teamName = $service_name . ($num_teams > 1 ? " (Team " . ($t+1) . ")" : "");
                $stmt = $pdo->prepare("INSERT INTO service_teams (service_date, service_name, batch_id) VALUES (?, ?, ?)");
                $stmt->execute([$service_date, $teamName, $batch_id]);
                $team_id = $pdo->lastInsertId();

                foreach ($teams[$t] as $vp => $members) {
                    foreach ($members as $singer) {
                        $stmt2 = $pdo->prepare("INSERT INTO service_team_members (service_team_id, user_id, voice_part, performance_level) VALUES (?, ?, ?, ?)");
                        $stmt2->execute([$team_id, $singer['id'], $singer['voice_part'], $singer['performance_level']]);
                    }
                }
            }
            $message = "$num_teams service team(s) generated successfully.";
            $tab = 'groups';

        } elseif ($action == 'delete_service_team') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM service_teams WHERE id=?")->execute([$id]);
            $message = "Service team deleted.";
            $tab = 'groups';

        } elseif ($action == 'delete_batch') {
            $batch_id = $_POST['batch_id'];
            $pdo->prepare("DELETE FROM service_teams WHERE batch_id = ?")->execute([$batch_id]);
            $message = "Batch deleted.";
            $tab = 'groups';
        }
    }

    // ----- GROUP SETTINGS (save default values) -----
    elseif ($sub == 'group_settings') {
        if ($action == 'save_settings') {
            $default_soprano = (int)$_POST['default_soprano'];
            $default_alto = (int)$_POST['default_alto'];
            $default_tenor = (int)$_POST['default_tenor'];
            $default_bass = (int)$_POST['default_bass'];
            $default_rotation = $_POST['default_rotation'];

            $pdo->prepare("REPLACE INTO group_settings (setting_key, setting_value) VALUES ('default_soprano', ?)")->execute([$default_soprano]);
            $pdo->prepare("REPLACE INTO group_settings (setting_key, setting_value) VALUES ('default_alto', ?)")->execute([$default_alto]);
            $pdo->prepare("REPLACE INTO group_settings (setting_key, setting_value) VALUES ('default_tenor', ?)")->execute([$default_tenor]);
            $pdo->prepare("REPLACE INTO group_settings (setting_key, setting_value) VALUES ('default_bass', ?)")->execute([$default_bass]);
            $pdo->prepare("REPLACE INTO group_settings (setting_key, setting_value) VALUES ('default_rotation', ?)")->execute([$default_rotation]);

            $message = "Settings saved.";
            $tab = 'groups';
        }
    }

    // Redirect back with message
    header("Location: ?page=music_evangelism&tab=$tab&success=" . urlencode($message ?: $error));
    exit;
}

// If no action, redirect back
header("Location: ?page=music_evangelism&tab=playlist");
exit;