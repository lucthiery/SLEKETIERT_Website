<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $db   = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            header('Location: /admin/dashboard.php');
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SELEKTIERT Admin — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #111;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 12px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
        }
        .login-card .brand {
            text-align: center;
            margin-bottom: 1.8rem;
        }
        .login-card .brand span {
            font-size: 1.7rem;
            font-weight: 900;
            letter-spacing: 2px;
            background: #ffe137;
            padding: 4px 14px;
            border-radius: 4px;
        }
        .login-card .brand sub {
            font-size: 0.75rem;
            display: block;
            color: #666;
            margin-top: 6px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .btn-login {
            background: #ffe137;
            border: none;
            color: #000;
            font-weight: 700;
            letter-spacing: 1px;
            width: 100%;
            padding: 0.65rem;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .btn-login:hover { background: #f0d000; }
        .form-control:focus {
            border-color: #ffe137;
            box-shadow: 0 0 0 0.2rem rgba(255,225,55,0.35);
        }
        .forgot-link {
            font-size: 0.85rem;
            color: #666;
            text-decoration: none;
        }
        .forgot-link:hover { color: #000; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="brand">
        <span>SELEKTIERT_</span>
        <sub>Admin Portal</sub>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label class="form-label fw-semibold small">Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold small">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-4 text-end">
            <a href="/admin/reset.php" class="forgot-link">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-login">Sign In</button>
    </form>
</div>
</body>
</html>
