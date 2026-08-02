<?php
/**
 * CMS Login Page — 10x Modern Redesign
 */

require_once __DIR__ . '/../includes/config.php';

session_start();

// Already logged in → redirect
if (!empty($_SESSION['cms_user_id'])) {
    header('Location: packages.php');
    exit;
}

$error   = '';
$success = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['cms_user_id']  = $user['id'];
            $_SESSION['cms_username'] = $user['username'];
            header('Location: packages.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Bardiya Eco CMS</title>
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

        /* Ambient floating background elements */
        .ambient-glow {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.25) 0%, rgba(0,0,0,0) 70%);
            top: 10%;
            left: 15%;
            filter: blur(50px);
            pointer-events: none;
        }
        .ambient-glow-2 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.15) 0%, rgba(0,0,0,0) 70%);
            bottom: 5%;
            right: 10%;
            filter: blur(60px);
            pointer-events: none;
        }

        .login-wrap {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.2);
            padding: 44px 40px;
            width: min(440px, 94vw);
            position: relative;
            z-index: 10;
            animation: cardAppear 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(24px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-brand .icon-wrap {
            width: 56px;
            height: 56px;
            margin: 0 auto 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            color: #fff;
        }
        .login-brand h1 {
            font-size: 1.45rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .login-brand p {
            font-size: 0.88rem;
            color: #64748b;
            margin-top: 4px;
            font-weight: 500;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.86rem;
            font-weight: 500;
        }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.86rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 0.84rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.92rem;
            color: #0f172a;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        .input-wrap input:focus {
            outline: none;
            border-color: #059669;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.12);
        }
        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px;
        }
        .toggle-pw:hover { color: #059669; }

        .forgot-link {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #059669;
            text-decoration: none;
            transition: color 0.15s;
        }
        .forgot-link:hover { color: #047857; text-decoration: underline; }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
            transition: all 0.2s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.45);
            transform: translateY(-1px);
        }

        .footer-note {
            text-align: center;
            margin-top: 24px;
            font-size: 0.78rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="ambient-glow"></div>
<div class="ambient-glow-2"></div>

<div class="login-wrap">
    <div class="login-brand">
        <div class="icon-wrap">🌿</div>
        <h1>Bardiya Eco CMS</h1>
        <p>Sign in to manage your sustainable homestay</p>
    </div>

    <?php if ($success): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <div class="input-wrap">
                <input type="text" id="username" name="username" autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Enter your admin username" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <label for="password" style="margin-bottom:0;">Password</label>
                <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            </div>
            <div class="input-wrap">
                <input type="password" id="password" name="password" autocomplete="current-password" placeholder="••••••••" required>
                <button type="button" class="toggle-pw" onclick="togglePasswordVisibility()">Show</button>
            </div>
        </div>

        <button type="submit" class="btn-login">Sign In to Dashboard</button>
    </form>

    <div class="footer-note">
        Protected by Bardiya Eco-Friendly Portal Security
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const pwInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.toggle-pw');
    if (pwInput.type === 'password') {
        pwInput.type = 'text';
        toggleBtn.textContent = 'Hide';
    } else {
        pwInput.type = 'password';
        toggleBtn.textContent = 'Show';
    }
}
</script>
</body>
</html>
