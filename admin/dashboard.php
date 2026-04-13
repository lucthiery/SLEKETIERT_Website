<?php
require_once __DIR__ . '/includes/auth.php';
$current_user = require_admin();

$db    = get_db();
$users = $db->query('SELECT id, first_name, last_name, email, role, is_admin, profile_picture, birthdate FROM users ORDER BY last_name, first_name')->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SELEKTIERT Admin — Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; font-family: 'Segoe UI', sans-serif; }
        .topbar {
            background: #ffe137;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .topbar .brand { font-weight: 900; font-size: 1.2rem; letter-spacing: 2px; }
        .topbar .user-info { font-size: 0.85rem; color: #333; }
        .content { padding: 2rem 1.5rem; max-width: 1100px; margin: auto; }
        .page-title { font-weight: 800; font-size: 1.6rem; margin-bottom: 1.5rem; }
        .table thead th { background: #111; color: #fff; font-weight: 600; border: none; }
        .table tbody tr:hover { background: #fffbe0; }
        .avatar-sm {
            width: 40px; height: 40px;
            border-radius: 50%; object-fit: cover;
            background: #ddd;
        }
        .avatar-placeholder {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: #ffe137;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem; color: #000;
        }
        .badge-admin { background: #ffe137; color: #000; font-weight: 700; }
        .btn-view { background: #ffe137; border: none; color: #000; font-weight: 600; font-size: 0.8rem; }
        .btn-view:hover { background: #f0d000; }
        .card-stat { border: none; border-radius: 10px; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
        .logout-link { color: #000; text-decoration: none; font-weight: 600; font-size: 0.85rem; }
        .logout-link:hover { color: #d30505; }
    </style>
</head>
<body>

<div class="topbar">
    <span class="brand">SELEKTIERT_</span>
    <div class="d-flex align-items-center gap-3">
        <a href="/admin/profile.php?id=<?= $current_user['id'] ?>" class="user-info" style="text-decoration:none;color:#000;">
            <i class="fas fa-user-shield me-1"></i>
            <?= htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']) ?>
        </a>
        <a href="/admin/logout.php" class="logout-link"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
    </div>
</div>

<div class="content">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-stat p-3 text-center">
                <div class="fw-bold fs-2"><?= count($users) ?></div>
                <div class="text-muted small">Total Members</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stat p-3 text-center">
                <div class="fw-bold fs-2"><?= count(array_filter($users, fn($u) => $u['is_admin'])) ?></div>
                <div class="text-muted small">Admins</div>
            </div>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="page-title mb-0">Members</h1>
        <input type="text" id="search" class="form-control w-auto" placeholder="Search..." style="max-width:200px;">
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="members-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Birthdate</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <?php if ($u['profile_picture']): ?>
                                <img src="/admin/uploads/<?= htmlspecialchars($u['profile_picture']) ?>"
                                     class="avatar-sm" alt="">
                            <?php else: ?>
                                <span class="avatar-placeholder">
                                    <?= strtoupper(substr($u['first_name'],0,1) . substr($u['last_name'],0,1)) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                            <?php if ($u['is_admin']): ?>
                                <span class="badge badge-admin ms-1">Admin</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['role'] ?? '—') ?></td>
                        <td><?= $u['birthdate'] ? htmlspecialchars($u['birthdate']) : '—' ?></td>
                        <td>
                            <a href="/admin/profile.php?id=<?= $u['id'] ?>" class="btn btn-view btn-sm">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('search').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#members-table tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
