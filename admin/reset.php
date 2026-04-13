<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$db    = get_db();
$step  = 'request'; // 'request' | 'sent' | 'reset' | 'done'
$error = '';

// Step 1 — handle token from email link
$token = trim($_GET['token'] ?? '');
if ($token) {
    $stmt = $db->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_expires > ?');
    $stmt->execute([$token, time()]);
    $user = $stmt->fetch();
    if ($user) {
        $step = 'reset';
    } else {
        $error = 'This reset link is invalid or has expired.';
        $step  = 'request';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 1 — submit email
    if ($_POST['step'] === 'request') {
        $email = trim($_POST['email'] ?? '');
        $stmt  = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user  = $stmt->fetch();

        if ($user) {
            $tok     = bin2hex(random_bytes(32));
            $expires = time() + 3600;
            $db->prepare('UPDATE users SET reset_token=?, reset_expires=? WHERE id=?')
               ->execute([$tok, $expires, $user['id']]);

            $reset_url = 'https://selektiert.com/admin/reset.php?token=' . $tok;
            $subject   = 'SELEKTIERT — Password Reset';
            $body      = "Hi {$user['first_name']},\n\n"
                       . "You requested a password reset for your SELEKTIERT account.\n\n"
                       . "Click the link below to set a new password (valid for 1 hour):\n\n"
                       . $reset_url . "\n\n"
                       . "If you did not request this, you can ignore this email.\n\n"
                       . "— SELEKTIERT Admin";
            $headers   = "From: admin@selektiert.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            mail($email, $subject, $body, $headers);
        }
        // Always show "sent" to avoid user enumeration
        $step = 'sent';

    // Step 2 — set new password
    } elseif ($_POST['step'] === 'reset') {
        $tok  = trim($_POST['token'] ?? '');
        $pass = $_POST['password'] ?? '';
        $conf = $_POST['password_confirm'] ?? '';

        $stmt = $db->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_expires > ?');
        $stmt->execute([$tok, time()]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Invalid or expired token.';
            $step  = 'request';
        } elseif (strlen($pass) < 8) {
            $error = 'Password must be at least 8 characters.';
            $step  = 'reset';
            $token = $tok;
        } elseif ($pass !== $conf) {
            $error = 'Passwords do not match.';
            $step  = 'reset';
            $token = $tok;
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare('UPDATE users SET password_hash=?, reset_token=NULL, reset_expires=NULL WHERE id=?')
               ->execute([$hash, $user['id']]);
            $step = 'done';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SELEKTIERT Admin — Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #111;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-wrap {
            background: #fff;
            border-radius: 12px;
            padding: 2.5rem 2rem;
            width: 100%; max-width: 400px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
        }
        .brand { text-align: center; margin-bottom: 1.8rem; }
        .brand span {
            font-size: 1.5rem; font-weight: 900; letter-spacing: 2px;
            background: #ffe137; padding: 4px 14px; border-radius: 4px;
        }
        .brand sub { font-size: 0.75rem; display: block; color: #666; margin-top: 6px; letter-spacing: 3px; text-transform: uppercase; }
        .btn-yellow { background: #ffe137; border: none; color: #000; font-weight: 700; width: 100%; padding: 0.65rem; border-radius: 6px; }
        .btn-yellow:hover { background: #f0d000; }
        .form-control:focus { border-color: #ffe137; box-shadow: 0 0 0 0.2rem rgba(255,225,55,0.35); }
        .back-link { font-size: 0.85rem; color: #666; text-decoration: none; }
        .back-link:hover { color: #000; }
    </style>
</head>
<body>
<div class="card-wrap">
    <div class="brand">
        <span>SELEKTIERT_</span>
        <sub>Password Reset</sub>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 'request'): ?>
        <p class="text-muted small mb-3">Enter your email address and we'll send you a reset link.</p>
        <form method="POST">
            <input type="hidden" name="step" value="request">
            <div class="mb-3">
                <label class="form-label fw-semibold small">Email</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <button type="submit" class="btn btn-yellow">Send Reset Link</button>
        </form>
        <div class="text-center mt-3">
            <a href="/admin/" class="back-link">← Back to login</a>
        </div>

    <?php elseif ($step === 'sent'): ?>
        <div class="text-center">
            <div style="font-size:2.5rem;">📬</div>
            <h6 class="fw-bold mt-2">Check your inbox</h6>
            <p class="text-muted small">If an account exists with that email, a reset link has been sent.</p>
            <a href="/admin/" class="back-link">← Back to login</a>
        </div>

    <?php elseif ($step === 'reset'): ?>
        <p class="text-muted small mb-3">Choose a new password (min. 8 characters).</p>
        <form method="POST">
            <input type="hidden" name="step" value="reset">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold small">New Password</label>
                <input type="password" name="password" class="form-control" minlength="8" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold small">Confirm Password</label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-yellow">Set New Password</button>
        </form>

    <?php elseif ($step === 'done'): ?>
        <div class="text-center">
            <div style="font-size:2.5rem;">✅</div>
            <h6 class="fw-bold mt-2">Password updated!</h6>
            <p class="text-muted small">You can now log in with your new password.</p>
            <a href="/admin/" class="btn btn-yellow d-block mt-3">Go to Login</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
