<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/migrate.php';
$current_user = require_admin();
$db = get_db();
run_migrations($db);

$topbar_page = 'cms';
$topbar_cms  = 'music';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $artist      = trim($_POST['artist'] ?? '');
        $date        = trim($_POST['date'] ?? '') ?: null;
        $embed_url   = trim($_POST['embed_url'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$title) { $error = 'Title is required.'; goto render; }

        if ($id) {
            $db->prepare('UPDATE music SET title=?,artist=?,date=?,embed_url=?,description=? WHERE id=?')
               ->execute([$title,$artist,$date,$embed_url,$description,$id]);
            $success = 'Track updated.';
        } else {
            $db->prepare('INSERT INTO music (title,artist,date,embed_url,description) VALUES (?,?,?,?,?)')
               ->execute([$title,$artist,$date,$embed_url,$description]);
            $success = 'Track added.';
        }
    }

    if ($action === 'delete') {
        $db->prepare('DELETE FROM music WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
        $success = 'Track deleted.';
    }
}

render:
$edit  = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM music WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$tracks = $db->query('SELECT * FROM music ORDER BY date DESC, id DESC')->fetchAll();

function embed_html(string $url): string {
    if (!$url) return '';
    // SoundCloud
    if (str_contains($url, 'soundcloud.com')) {
        $enc = urlencode($url);
        return "<iframe width='100%' height='100' scrolling='no' frameborder='no' allow='autoplay'
            src='https://w.soundcloud.com/player/?url={$enc}&color=%23ffe137&auto_play=false&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false'>
            </iframe>";
    }
    // Mixcloud
    if (str_contains($url, 'mixcloud.com')) {
        $enc = urlencode($url);
        return "<iframe width='100%' height='60' src='https://www.mixcloud.com/widget/iframe/?hide_cover=1&feed={$enc}' frameborder='0'></iframe>";
    }
    // YouTube
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        return "<iframe width='100%' height='80' src='https://www.youtube.com/embed/{$m[1]}' frameborder='0' allowfullscreen></iframe>";
    }
    // Generic iframe fallback
    return "<a href='" . htmlspecialchars($url) . "' target='_blank' class='btn btn-sm btn-outline-secondary'><i class='fas fa-external-link-alt me-1'></i>Open Link</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SELEKTIERT CMS — Music</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; font-family: 'Segoe UI', sans-serif; }
        .content { padding: 2rem 1.5rem; max-width: 1000px; margin: auto; }
        .page-title { font-weight: 800; font-size: 1.5rem; }
        .btn-yellow { background: #ffe137; border: none; color: #000; font-weight: 700; }
        .btn-yellow:hover { background: #f0d000; }
        .form-control:focus { border-color: #ffe137; box-shadow: 0 0 0 0.2rem rgba(255,225,55,.35); }
        .form-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,.08); padding: 1.75rem; margin-bottom: 2rem; }
        .section-label { font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px; color: #888; margin-bottom: 0.3rem; }
        .track-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.07); padding: 1.2rem; margin-bottom: 1rem; }
        .track-title { font-weight: 700; font-size: 1rem; }
        .track-meta { font-size: 0.8rem; color: #888; margin-bottom: 0.6rem; }
        .track-embed { margin-top: 0.5rem; }
        .platform-hint { font-size: 0.75rem; color: #aaa; margin-top: 4px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/topbar.php'; ?>

<div class="content">

    <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- Form -->
    <div class="form-card">
        <h2 class="page-title mb-4"><?= $edit ? 'Edit Track' : 'Add Track' ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="section-label">Title *</div>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($edit['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <div class="section-label">Artist</div>
                    <input type="text" name="artist" class="form-control" value="<?= htmlspecialchars($edit['artist'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <div class="section-label">Date</div>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($edit['date'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <div class="section-label">Embed URL</div>
                    <input type="url" name="embed_url" class="form-control"
                           placeholder="https://soundcloud.com/… or mixcloud.com/… or youtu.be/…"
                           value="<?= htmlspecialchars($edit['embed_url'] ?? '') ?>">
                    <div class="platform-hint">Supports SoundCloud, Mixcloud, and YouTube links.</div>
                </div>
                <div class="col-12">
                    <div class="section-label">Description</div>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-yellow px-4"><i class="fas fa-save me-1"></i><?= $edit ? 'Update' : 'Add Track' ?></button>
                <?php if ($edit): ?>
                    <a href="/admin/cms/music.php" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Track list -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="page-title mb-0">Tracks <span class="text-muted fs-6 fw-normal">(<?= count($tracks) ?>)</span></h2>
    </div>

    <?php if (empty($tracks)): ?>
        <div class="text-muted">No tracks yet.</div>
    <?php else: ?>
        <?php foreach ($tracks as $t): ?>
        <div class="track-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="track-title"><?= htmlspecialchars($t['title']) ?></div>
                    <div class="track-meta">
                        <?php if ($t['artist']): ?><i class="fas fa-user me-1"></i><?= htmlspecialchars($t['artist']) ?><?php endif; ?>
                        <?php if ($t['date']):   ?><span class="ms-2"><i class="fas fa-calendar me-1"></i><?= htmlspecialchars($t['date']) ?></span><?php endif; ?>
                    </div>
                    <?php if ($t['description']): ?>
                        <div class="text-muted small mb-1"><?= htmlspecialchars($t['description']) ?></div>
                    <?php endif; ?>
                    <?php if ($t['embed_url']): ?>
                        <div class="track-embed"><?= embed_html($t['embed_url']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-1 ms-3 flex-shrink-0">
                    <a href="?edit=<?= $t['id'] ?>" class="btn btn-yellow btn-sm"><i class="fas fa-pen"></i></a>
                    <form method="POST" onsubmit="return confirm('Delete this track?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
