<?php
require_once __DIR__ . '/includes/auth.php';
$current_user = require_admin();
$db = get_db();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin/dashboard.php'); exit; }

$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { header('Location: /admin/dashboard.php'); exit; }

$error   = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $birthdate  = trim($_POST['birthdate'] ?? '') ?: null;
        $role       = trim($_POST['role'] ?? '');
        $is_admin   = isset($_POST['is_admin']) ? 1 : 0;

        // Handle avatar upload
        $profile_picture = $user['profile_picture'];
        if (!empty($_FILES['avatar']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowed)) {
                $error = 'Only JPEG, PNG, GIF, and WebP images are allowed.';
            } elseif ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5 MB.';
            } else {
                $ext             = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename        = 'avatar_' . $id . '_' . time() . '.' . strtolower($ext);
                $dest            = UPLOADS_DIR . $filename;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                    // Remove old avatar
                    if ($profile_picture && file_exists(UPLOADS_DIR . $profile_picture)) {
                        unlink(UPLOADS_DIR . $profile_picture);
                    }
                    $profile_picture = $filename;
                } else {
                    $error = 'Failed to upload image.';
                }
            }
        }

        if (!$error) {
            // Check email uniqueness
            $check = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $check->execute([$email, $id]);
            if ($check->fetch()) {
                $error = 'That email is already in use.';
            } else {
                $upd = $db->prepare('UPDATE users SET first_name=?, last_name=?, email=?, birthdate=?, role=?, is_admin=?, profile_picture=? WHERE id=?');
                $upd->execute([$first_name, $last_name, $email, $birthdate, $role, $is_admin, $profile_picture, $id]);
                $success = 'Profile updated.';
                // Refresh user data
                $stmt->execute([$id]);
                $user = $stmt->fetch();
            }
        }
    }

    if ($_POST['action'] === 'send_reset') {
        $token   = bin2hex(random_bytes(32));
        $expires = time() + 3600; // 1 hour
        $upd     = $db->prepare('UPDATE users SET reset_token=?, reset_expires=? WHERE id=?');
        $upd->execute([$token, $expires, $id]);

        $reset_url = 'https://selektiert.com/admin/reset.php?token=' . $token;
        $to        = $user['email'];
        $subject   = 'SELEKTIERT — Password Reset';
        $body      = "Hi {$user['first_name']},\n\n"
                   . "A password reset was requested for your SELEKTIERT account.\n\n"
                   . "Click the link below to set a new password (valid for 1 hour):\n\n"
                   . $reset_url . "\n\n"
                   . "If you did not request this, you can ignore this email.\n\n"
                   . "— SELEKTIERT Admin";
        $headers   = "From: admin@selektiert.com\r\n"
                   . "Reply-To: admin@selektiert.com\r\n"
                   . "Content-Type: text/plain; charset=UTF-8\r\n";

        if (mail($to, $subject, $body, $headers)) {
            $success = "Password reset email sent to {$user['email']}.";
        } else {
            $error = 'Failed to send email. Check server mail config.';
        }
    }

    if ($_POST['action'] === 'delete_user') {
        if ($id === (int)$current_user['id']) {
            $error = 'You cannot delete your own account.';
        } else {
            if ($user['profile_picture'] && file_exists(UPLOADS_DIR . $user['profile_picture'])) {
                unlink(UPLOADS_DIR . $user['profile_picture']);
            }
            $db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User deleted.'];
            header('Location: /admin/dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SELEKTIERT Admin — <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; font-family: 'Segoe UI', sans-serif; }
        .topbar {
            background: #ffe137;
            padding: 0.75rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .topbar .brand { font-weight: 900; font-size: 1.2rem; letter-spacing: 2px; }
        .content { padding: 2rem 1.5rem; max-width: 820px; margin: auto; }
        .card-profile { border: none; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,0.09); }
        .avatar-wrap {
            text-align: center; padding: 2rem 1rem 1rem;
        }
        .avatar-wrap img, .avatar-placeholder-lg {
            width: 110px; height: 110px;
            border-radius: 50%; object-fit: cover;
            border: 4px solid #ffe137;
        }
        .avatar-placeholder-lg {
            background: #ffe137;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 2.2rem; font-weight: 800; color: #000;
        }
        .btn-yellow { background: #ffe137; border: none; color: #000; font-weight: 700; }
        .btn-yellow:hover { background: #f0d000; }
        .form-control:focus, .form-select:focus {
            border-color: #ffe137;
            box-shadow: 0 0 0 0.2rem rgba(255,225,55,0.35);
        }
        .section-label {
            font-weight: 700; font-size: 0.7rem; text-transform: uppercase;
            letter-spacing: 2px; color: #888; margin-bottom: 0.4rem;
        }
        .logout-link { color: #000; text-decoration: none; font-weight: 600; font-size: 0.85rem; }
        .logout-link:hover { color: #d30505; }
        .back-link { color: #000; text-decoration: none; font-weight: 600; }
        .back-link:hover { color: #555; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <a href="/admin/dashboard.php" class="back-link"><i class="fas fa-arrow-left me-1"></i>Back</a>
        <span class="brand">SELEKTIERT_</span>
    </div>
    <a href="/admin/logout.php" class="logout-link"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
</div>

<div class="content">

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card card-profile mb-4">
        <!-- Avatar Section -->
        <div class="avatar-wrap">
            <?php if ($user['profile_picture']): ?>
                <img src="/admin/uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Avatar" id="avatar-preview">
            <?php else: ?>
                <span class="avatar-placeholder-lg" id="avatar-preview-placeholder">
                    <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
                </span>
            <?php endif; ?>
            <div class="mt-2 text-muted small">Click "Choose File" below to update photo</div>
        </div>

        <!-- Edit Form -->
        <div class="card-body px-4 pb-4">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="section-label">First Name</div>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= htmlspecialchars($user['first_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <div class="section-label">Last Name</div>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= htmlspecialchars($user['last_name']) ?>" required>
                    </div>
                    <div class="col-md-8">
                        <div class="section-label">Email Address</div>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <div class="section-label">Birthdate</div>
                        <input type="date" name="birthdate" class="form-control"
                               value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <div class="section-label">Role</div>
                        <input type="text" name="role" class="form-control"
                               value="<?= htmlspecialchars($user['role'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_admin" id="is_admin"
                                   <?= $user['is_admin'] ? 'checked' : '' ?>
                                   <?= ($id === (int)$current_user['id']) ? 'disabled' : '' ?>>
                            <label class="form-check-label fw-semibold" for="is_admin">Admin access</label>
                        </div>
                        <?php if ($id === (int)$current_user['id']): ?>
                            <input type="hidden" name="is_admin" value="1">
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <div class="section-label">Profile Picture</div>
                        <input type="file" name="avatar" class="form-control" accept="image/*" id="avatar-input">
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-yellow px-4">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Reset Card -->
    <div class="card card-profile mb-4">
        <div class="card-body px-4 py-3">
            <h6 class="fw-bold mb-1">Password Reset</h6>
            <p class="text-muted small mb-3">
                Send a password reset link to <strong><?= htmlspecialchars($user['email']) ?></strong>.
                The link expires after 1 hour.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="send_reset">
                <button type="submit" class="btn btn-yellow btn-sm px-3">
                    <i class="fas fa-envelope me-1"></i> Send Reset Email
                </button>
            </form>
        </div>
    </div>

    <!-- Danger Zone -->
    <?php if ($id !== (int)$current_user['id']): ?>
    <div class="card border-danger mb-4">
        <div class="card-body px-4 py-3">
            <h6 class="fw-bold text-danger mb-1">Danger Zone</h6>
            <p class="text-muted small mb-3">Permanently delete this user account. This cannot be undone.</p>
            <form method="POST" onsubmit="return confirm('Delete <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>? This cannot be undone.')">
                <input type="hidden" name="action" value="delete_user">
                <button type="submit" class="btn btn-danger btn-sm px-3">
                    <i class="fas fa-trash me-1"></i> Delete User
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live avatar preview
document.getElementById('avatar-input').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        let existing = document.getElementById('avatar-preview');
        if (!existing) {
            existing = document.getElementById('avatar-preview-placeholder');
        }
        const img = document.createElement('img');
        img.src = e.target.result;
        img.id = 'avatar-preview';
        img.style.cssText = 'width:110px;height:110px;border-radius:50%;object-fit:cover;border:4px solid #ffe137;';
        existing.replaceWith(img);
    };
    reader.readAsDataURL(file);
});
</script>
</body>
</html>
