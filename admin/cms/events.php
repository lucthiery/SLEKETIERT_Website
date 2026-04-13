<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/migrate.php';
$current_user = require_admin();
$db = get_db();
run_migrations($db);

$topbar_page = 'cms';
$topbar_cms  = 'events';

define('EVENT_UPLOADS', __DIR__ . '/../uploads/events/');
define('EVENT_UPLOADS_URL', '/admin/uploads/events/');
if (!is_dir(EVENT_UPLOADS)) mkdir(EVENT_UPLOADS, 0755, true);

$error = $success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $date        = trim($_POST['date'] ?? '') ?: null;
        $location    = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status      = in_array($_POST['status'] ?? '', ['upcoming','past']) ? $_POST['status'] : 'upcoming';

        if (!$title) { $error = 'Title is required.'; goto render; }

        // Handle image upload
        $image = $id ? ($db->prepare('SELECT image FROM events WHERE id=?')->execute([$id]) ? $db->query("SELECT image FROM events WHERE id=$id")->fetchColumn() : null) : null;
        if ($id) {
            $stmt = $db->prepare('SELECT image FROM events WHERE id=?');
            $stmt->execute([$id]);
            $image = $stmt->fetchColumn() ?: null;
        }

        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) { $error = 'Images only (JPEG/PNG/GIF/WebP).'; goto render; }
            if ($_FILES['image']['size'] > 10 * 1024 * 1024) { $error = 'Max 10 MB.'; goto render; }
            $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $filename = 'event_' . ($id ?: 'new') . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], EVENT_UPLOADS . $filename);
            if ($image && file_exists(EVENT_UPLOADS . $image)) unlink(EVENT_UPLOADS . $image);
            $image = $filename;
        }

        if ($id) {
            $db->prepare('UPDATE events SET title=?,date=?,location=?,description=?,image=?,status=? WHERE id=?')
               ->execute([$title,$date,$location,$description,$image,$status,$id]);
            $success = 'Event updated.';
        } else {
            $db->prepare('INSERT INTO events (title,date,location,description,image,status) VALUES (?,?,?,?,?,?)')
               ->execute([$title,$date,$location,$description,$image,$status]);
            $success = 'Event created.';
        }
    }

    if ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT image FROM events WHERE id=?');
        $stmt->execute([$id]);
        $img  = $stmt->fetchColumn();
        if ($img && file_exists(EVENT_UPLOADS . $img)) unlink(EVENT_UPLOADS . $img);
        $db->prepare('DELETE FROM events WHERE id=?')->execute([$id]);
        $success = 'Event deleted.';
    }
}

render:
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM events WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$events = $db->query('SELECT * FROM events ORDER BY date DESC, id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SELEKTIERT CMS — Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; font-family: 'Segoe UI', sans-serif; }
        .content { padding: 2rem 1.5rem; max-width: 1100px; margin: auto; }
        .page-title { font-weight: 800; font-size: 1.5rem; }
        .btn-yellow { background: #ffe137; border: none; color: #000; font-weight: 700; }
        .btn-yellow:hover { background: #f0d000; }
        .form-control:focus, .form-select:focus { border-color: #ffe137; box-shadow: 0 0 0 0.2rem rgba(255,225,55,.35); }
        .table thead th { background: #111; color: #fff; border: none; font-weight: 600; }
        .table tbody tr:hover { background: #fffbe0; }
        .event-thumb { width: 60px; height: 45px; object-fit: cover; border-radius: 4px; }
        .badge-upcoming { background: #ffe137; color: #000; font-weight: 700; }
        .badge-past { background: #ddd; color: #555; }
        .form-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,.08); padding: 1.75rem; margin-bottom: 2rem; }
        .section-label { font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px; color: #888; margin-bottom: 0.3rem; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/topbar.php'; ?>

<div class="content">

    <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- Form -->
    <div class="form-card">
        <h2 class="page-title mb-4"><?= $edit ? 'Edit Event' : 'New Event' ?></h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="section-label">Title *</div>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($edit['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <div class="section-label">Date</div>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($edit['date'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <div class="section-label">Status</div>
                    <select name="status" class="form-select">
                        <option value="upcoming" <?= ($edit['status'] ?? 'upcoming') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                        <option value="past"     <?= ($edit['status'] ?? '') === 'past' ? 'selected' : '' ?>>Past</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="section-label">Location</div>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($edit['location'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <div class="section-label">Flyer / Image</div>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <?php if (!empty($edit['image'])): ?>
                        <div class="mt-2"><img src="<?= EVENT_UPLOADS_URL . htmlspecialchars($edit['image']) ?>" style="height:60px;border-radius:4px;"></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <div class="section-label">Description</div>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-yellow px-4"><i class="fas fa-save me-1"></i><?= $edit ? 'Update' : 'Create' ?></button>
                <?php if ($edit): ?>
                    <a href="/admin/cms/events.php" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="page-title mb-0">All Events <span class="text-muted fs-6 fw-normal">(<?= count($events) ?>)</span></h2>
    </div>

    <?php if (empty($events)): ?>
        <div class="text-muted">No events yet.</div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th></th><th>Title</th><th>Date</th><th>Location</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($events as $e): ?>
                <tr>
                    <td>
                        <?php if ($e['image']): ?>
                            <img src="<?= EVENT_UPLOADS_URL . htmlspecialchars($e['image']) ?>" class="event-thumb" alt="">
                        <?php else: ?>
                            <div style="width:60px;height:45px;background:#eee;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:.8rem;"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= htmlspecialchars($e['title']) ?></td>
                    <td class="text-muted small"><?= $e['date'] ?: '—' ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($e['location'] ?: '—') ?></td>
                    <td><span class="badge badge-<?= $e['status'] ?>"><?= ucfirst($e['status']) ?></span></td>
                    <td>
                        <a href="?edit=<?= $e['id'] ?>" class="btn btn-yellow btn-sm me-1"><i class="fas fa-pen"></i></a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this event?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
