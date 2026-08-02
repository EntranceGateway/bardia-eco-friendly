<?php
/**
 * CMS Reset Password — Step 2 (Modern Redesign)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
if (!empty($_SESSION['cms_user_id'])) {
    header('Location: packages.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    die('Invalid or missing token.');
}

// Verify token
$stmt = $conn->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expiry > NOW() LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $error = 'Token is invalid or has expired. Please request a new link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($newPass) || empty($confirmPass)) {
        $error = 'Both fields are required.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPass) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        $hashedPass = password_hash($newPass, PASSWORD_BCRYPT);
        
        // Update password and clear token
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        $stmt->bind_param('si', $hashedPass, $user['id']);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = 'Password reset successfully! You can now log in.';
            header('Location: login.php');
            exit;
        } else {
            $error = 'Failed to update password. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — Bardiya Eco CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 80% 20%, #065f46 0%, #044e39 35%, #022c22 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
            position: relative;
        }

        .ambient-glow {
            position: absolute; width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.25) 0%, rgba(0,0,0,0) 70%);
            top: 10%; left: 15%; filter: blur(50px); pointer-events: none;
        }

        .login-wrap {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            padding: 44px 40px;
            width: min(440px, 94vw);
            z-index: 10;
        }

        .login-brand { text-align: center; margin-bottom: 28px; }
        .login-brand h1 { font-size: 1.45rem; font-weight: 800; color: #0f172a; }
        .login-brand p { font-size: 0.88rem; color: #64748b; margin-top: 4px; }

        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.86rem; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.86rem; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.84rem; font-weight: 600; color: #334155; margin-bottom: 8px; }
        .form-group input {
            width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 12px;
            font-family: inherit; font-size: 0.92rem; color: #0f172a; background: #f8fafc;
            transition: all 0.2s ease;
        }
        .form-group input:focus { outline: none; border-color: #059669; background: #ffffff; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.12); }

        .btn-login {
            width: 100%; padding: 13px; background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff; border: none; border-radius: 12px; font-family: inherit; font-size: 0.95rem; font-weight: 700;
            cursor: pointer; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35); transition: all 0.2s ease;
        }
        .btn-login:hover { background: linear-gradient(135deg, #047857 0%, #064e3b 100%); transform: translateY(-1px); }

        .back-link { display: block; text-align: center; margin-top: 20px; font-size: 0.84rem; font-weight: 600; color: #059669; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="ambient-glow"></div>

<div class="login-wrap">
    <div class="login-brand">
        <div style="font-size: 2rem; margin-bottom: 8px;">🔒</div>
        <h1>Set New Password</h1>
        <p>Enter a secure password for your administrator account</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="login.php" class="btn-login" style="display:block;text-align:center;text-decoration:none;">Sign In Now</a>
    <?php elseif ($user): ?>
        <form method="POST">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="At least 8 characters" autofocus>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-enter password">
            </div>
            <button type="submit" class="btn-login">Update Account Password</button>
        </form>
    <?php endif; ?>
    
    <?php if (!$success): ?>
        <a href="login.php" class="back-link">&larr; Back to Login</a>
    <?php endif; ?>
</div>
</body>
</html>
