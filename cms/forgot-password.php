<?php
/**
 * CMS Forgot Password — Step 1 (Modern Redesign)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

session_start();
if (!empty($_SESSION['cms_user_id'])) {
    header('Location: packages.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Email is required.';
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Generate secure token
            $token  = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
            $stmt->bind_param('ssi', $token, $expiry, $user['id']);
            
            if ($stmt->execute()) {
                // Build reset link
                $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host     = $_SERVER['HTTP_HOST'];
                $base     = dirname($_SERVER['SCRIPT_NAME']);
                $resetUrl = $scheme . '://' . $host . $base . '/reset-password.php?token=' . $token;

                // Send Email
                $body = "
                    <h2>Password Reset Request</h2>
                    <p>Hello {$user['username']},</p>
                    <p>We received a request to reset your password for Bardiya Eco CMS.</p>
                    <p>Click the link below to reset your password. This link will expire in 1 hour.</p>
                    <p><a href='$resetUrl' style='display:inline-block;background:#059669;color:#fff;padding:10px 20px;text-decoration:none;border-radius:8px;'>Reset Password</a></p>
                    <p>If you didn't request this, you can safely ignore this email.</p>
                ";

                $mailResult = sendEmail($email, $user['username'], 'Password Reset - Bardiya Eco CMS', $body);
                
                if ($mailResult['success']) {
                    $success = 'If an account exists with that email, a reset link has been sent.';
                } else {
                    $error = 'Failed to send email. Please try again later.';
                }
            } else {
                $error = 'An error occurred. Please try again.';
            }
            $stmt->close();
        } else {
            $success = 'If an account exists with that email, a reset link has been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Bardiya Eco CMS</title>
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
        <div style="font-size: 2rem; margin-bottom: 8px;">🔑</div>
        <h1>Reset Your Password</h1>
        <p>Enter your administrator email to receive a secure recovery link</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="admin@bardiaecofriendly.com" autofocus>
        </div>
        <button type="submit" class="btn-login">Send Password Reset Link</button>
        <a href="login.php" class="back-link">&larr; Back to Login</a>
    </form>
</div>
</body>
</html>
