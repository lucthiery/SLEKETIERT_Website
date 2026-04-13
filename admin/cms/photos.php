<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/migrate.php';
$current_user = require_admin();
$db = get_db();
run_migrations($db);

$topbar_page = 'cms';
$topbar_cms  = 'photos';

define('PHOTO_UPLOADS', __DIR__ . '/../uploads/photos/');
define('PHOTO_UPLOADS_URL', '/admin/uploads/photos/');
if (!is_dir(PHOTO_UPLOADS)) mkdir(PHOTO_UPLOADS, 0755, true);

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $album       = trim($_POST['album'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $files       = $_FILES['photos'] ?? [];
        $allowed     = ['image/jpeg','image/png','image/gif','image/webp'];
        $uploaded    = 0;

        if (empty($files['name'][0])) { $error = 'Please select at least one photo.'; goto render; }

        foreach ($files['name'] as $i => $name) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $files['tmp_name'][$i]);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) continue;
            if ($files['size'][$i] > 20 * 1024 * 1024) continue;

            $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $title    = pathinfo($name, PATHINFO_FILENAME);
            $filename = 'photo_' . time() . '_' . $i . '.' . $ext;
            move_uploaded_file($files['tmp_name'][$i], PHOTO_UPLOADS . $filename);

            $db->prepare('INSERT INTO photos (title,filename,album,description) VALUES (?,?,?,?)')
               ->execute([$title, $filename, $album ?: null, $description ?: null]);
            $uploaded++;
        }
        $success = "Uploaded $uploaded photo(s).";
    }

    if ($action === 'update') {
        $id    = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $album = trim($_POST['album'] ?? '');
        $db->prepare('UPDATE photos SET title=?, album=? WHERE id=?')->execute([$title, $album ?: null, $id]);
        $success = 'Photo updated.';
    }

    if ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT filename FROM photos WHERE id=?');
        $stmt->execute([$id]);
        $file = $stmt->fetchColumn();
        if ($file && file_exists(PHOTO_UPLOADS . $file)) unlink(PHOTO_UPLOADS . $file);
        $db->prepare('DELETE FROM photos WHERE id=?')->execute([$id]);
        $success = 'Photo deleted.';
    }
}

render:
// Get all albums for filter
$albums = $db->query("SELECT DISTINCT album FROM photos WHERE album IS NOT NULL ORDER BY album")->fetchAll(PDO::FETCH_COLUMN);
$filter_album = $_GET['album'] ?? '';

if ($filter_album) {
    $stmt = $db->prepare('SELECT * FROM photos WHERE album=? ORDER BY id DESC');
    $stmt->execute([$filter_album]);
    $photos = $stmt->fetchAll();
} else {
    $photos = $db->query('SELECT * FROM photos ORDER BY id DESC')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SELEKTIERT CMS — Photos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; font-family: 'Segoe UI', sans-serif; }
        .content { padding: 2rem 1.5rem; max-width: 1200px; margin: auto; }
        .page-title { font-weight: 800; font-size: 1.5rem; }
        .btn-yellow { background: #ffe137; border: none; color: #000; font-weight: 700; }
        .btn-yellow:hover { background: #f0d000; }
        .form-control:focus, .form-select:focus { border-color: #ffe137; box-shadow: 0 0 0 0.2rem rgba(255,225,55,.35); }
        .form-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,.08); padding: 1.75rem; margin-bottom: 2rem; }
        .section-label { font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px; color: #888; margin-bottom: 0.3rem; }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
        .photo-card { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
        .photo-card img { width: 100%; height: 140px; object-fit: cover; display: block; }
        .photo-card .photo-info { padding: 0.6rem; }
        .photo-card .photo-title { font-size: 0.8rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .photo-card .photo-album { font-size: 0.72rem; color: #888; }
        .photo-card .photo-actions { display: flex; gap: 4px; margin-top: 6px; }
        .album-pill {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 0.78rem; font-weight: 600; cursor: pointer;
            background: #eee; color: #555; text-decoration: none;
        }
        .album-pill:hover, .album-pill.active { background: #ffe137; color: #000; }
        .drop-zone {
            border: 2px dashed #ccc; border-radius: 8px; padding: 2rem;
            text-align: center; color: #aaa; cursor: pointer; transition: border-color .2s;
        }
        .drop-zone.dragover { border-color: #ffe137; background: #fffbe0; color: #000; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/topbar.php'; ?>

<div class="content">

    <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- Upload Form -->
    <div class="form-card">
        <h2 class="page-title mb-4">Upload Photos</h2>
        <form method="POST" enctype="multipart/form-data" id="upload-form">
            <input type="hidden" name="action" value="upload">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="section-label">Album</div>
                    <input type="text" name="album" class="form-control" placeholder="e.g. Event 2026-04" list="album-list">
                    <datalist id="album-list">
                        <?php foreach ($albums as $a): ?><option value="<?= htmlspecialchars($a) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-8">
                    <div class="section-label">Photos</div>
                    <div class="drop-zone" id="drop-zone">
                        <i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block"></i>
                        Drop photos here or <strong>click to browse</strong>
                        <input type="file" name="photos[]" id="photo-input" multiple accept="image/*" style="display:none">
                    </div>
                    <div id="preview-list" class="d-flex flex-wrap gap-2 mt-2"></div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-yellow px-4"><i class="fas fa-upload me-1"></i>Upload</button>
            </div>
        </form>
    </div>

    <!-- Album filter -->
    <?php if ($albums): ?>
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <span class="text-muted small me-1">Filter:</span>
        <a href="/admin/cms/photos.php" class="album-pill <?= !$filter_album ? 'active' : '' ?>">All</a>
        <?php foreach ($albums as $a): ?>
            <a href="?album=<?= urlencode($a) ?>" class="album-pill <?= $filter_album === $a ? 'active' : '' ?>">
                <?= htmlspecialchars($a) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Grid -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="page-title mb-0">
            Photos <span class="text-muted fs-6 fw-normal">(<?= count($photos) ?>)</span>
        </h2>
    </div>

    <?php if (empty($photos)): ?>
        <div class="text-muted">No photos yet.</div>
    <?php else: ?>
    <div class="photo-grid">
        <?php foreach ($photos as $p): ?>
        <div class="photo-card">
            <img src="<?= PHOTO_UPLOADS_URL . htmlspecialchars($p['filename']) ?>" alt="<?= htmlspecialchars($p['title'] ?? '') ?>"
                 loading="lazy" onclick="openLightbox(this.src)" style="cursor:zoom-in;">
            <div class="photo-info">
                <div class="photo-title"><?= htmlspecialchars($p['title'] ?: $p['filename']) ?></div>
                <?php if ($p['album']): ?>
                    <div class="photo-album"><i class="fas fa-folder me-1"></i><?= htmlspecialchars($p['album']) ?></div>
                <?php endif; ?>
                <div class="photo-actions">
                    <button class="btn btn-yellow btn-sm flex-fill" onclick="editPhoto(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['title'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($p['album'] ?? '')) ?>')">
                        <i class="fas fa-pen"></i>
                    </button>
                    <form method="POST" class="flex-fill" onsubmit="return confirm('Delete this photo?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-outline-danger btn-sm w-100"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Edit modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Edit Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Title</label>
                    <input type="text" name="title" id="edit-title" class="form-control">
                </div>
                <div>
                    <label class="form-label small fw-semibold">Album</label>
                    <input type="text" name="album" id="edit-album" class="form-control" list="album-list">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="submit" class="btn btn-yellow btn-sm px-3">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Lightbox -->
<div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out;" onclick="this.style.display='none'">
    <img id="lightbox-img" src="" style="max-width:90vw;max-height:90vh;border-radius:6px;">
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Drag & drop / click to select
const zone  = document.getElementById('drop-zone');
const input = document.getElementById('photo-input');
zone.addEventListener('click', () => input.click());
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('dragover'); input.files = e.dataTransfer.files; showPreviews(input.files); });
input.addEventListener('change', () => showPreviews(input.files));
function showPreviews(files) {
    const list = document.getElementById('preview-list');
    list.innerHTML = '';
    [...files].forEach(f => {
        const img = document.createElement('img');
        img.style.cssText = 'width:60px;height:60px;object-fit:cover;border-radius:4px;border:2px solid #ffe137;';
        const r = new FileReader();
        r.onload = e => img.src = e.target.result;
        r.readAsDataURL(f);
        list.appendChild(img);
    });
}

// Edit modal
function editPhoto(id, title, album) {
    document.getElementById('edit-id').value    = id;
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-album').value = album;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Lightbox
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').style.display = 'flex';
}
</script>
</body>
</html>
