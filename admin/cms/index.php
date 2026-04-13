<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/migrate.php';
$current_user = require_admin();
$db = get_db();
run_migrations($db);

$topbar_page = 'cms';

$counts = [
    'events' => $db->query('SELECT COUNT(*) FROM events')->fetchColumn(),
    'photos' => $db->query('SELECT COUNT(*) FROM photos')->fetchColumn(),
    'music'  => $db->query('SELECT COUNT(*) FROM music')->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SELEKTIERT CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; font-family: 'Segoe UI', sans-serif; }
        .content { padding: 3rem 1.5rem; max-width: 900px; margin: auto; }
        .page-title { font-weight: 900; font-size: 2rem; margin-bottom: 0.4rem; }
        .cms-card {
            background: #fff; border-radius: 14px;
            box-shadow: 0 2px 16px rgba(0,0,0,.08);
            padding: 2rem; text-decoration: none; color: #000;
            display: block; transition: transform .15s, box-shadow .15s;
        }
        .cms-card:hover { transform: translateY(-3px); box-shadow: 0 6px 24px rgba(0,0,0,.13); color: #000; }
        .cms-card .icon {
            width: 56px; height: 56px; border-radius: 12px;
            background: #ffe137; display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: 1rem;
        }
        .cms-card h3 { font-weight: 800; font-size: 1.2rem; margin-bottom: 0.2rem; }
        .cms-card p { color: #888; font-size: 0.85rem; margin: 0; }
        .cms-card .count { font-size: 2rem; font-weight: 900; line-height: 1; margin-bottom: 0.25rem; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/topbar.php'; ?>

<div class="content">
    <h1 class="page-title">Content Management</h1>
    <p class="text-muted mb-4">Manage events, photos, and music for the SELEKTIERT website.</p>

    <div class="row g-4">
        <div class="col-md-4">
            <a href="/admin/cms/events.php" class="cms-card">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="count"><?= $counts['events'] ?></div>
                <h3>Events</h3>
                <p>Upcoming and past events with flyers and details.</p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/admin/cms/photos.php" class="cms-card">
                <div class="icon"><i class="fas fa-images"></i></div>
                <div class="count"><?= $counts['photos'] ?></div>
                <h3>Photos</h3>
                <p>Photo gallery organized by albums.</p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/admin/cms/music.php" class="cms-card">
                <div class="icon"><i class="fas fa-music"></i></div>
                <div class="count"><?= $counts['music'] ?></div>
                <h3>Music</h3>
                <p>Mixes and tracks from SoundCloud, Mixcloud, YouTube.</p>
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
