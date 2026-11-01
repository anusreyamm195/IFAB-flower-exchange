<?php
require_once __DIR__ . '/config/config.php';

$error = '';
$info = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Admin accounts are stored separately from buyer and seller accounts.
    $adminStmt = db()->prepare(
        "SELECT * FROM admins WHERE email = ? AND status = 'active' LIMIT 1"
    );
    $adminStmt->execute([$email]);
    $admin = $adminStmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role'], $_SESSION['user_email']);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $update = db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?');
        $update->execute([$admin['id']]);
        header('Location: ' . url('admin/dashboard.php'));
        exit;
    }

    // Regular users can sign in as either a buyer or a seller/grower.
    $userStmt = db()->prepare(
        "SELECT * FROM users WHERE email = ? AND role IN ('buyer', 'grower') LIMIT 1"
    );
    $userStmt->execute([$email]);
    $user = $userStmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Incorrect email or password.';
    } elseif ($user['status'] === 'pending') {
        $info = 'Your account is waiting for administrator approval.';
    } elseif ($user['status'] !== 'active') {
        $error = 'Your account is currently blocked. Please contact IFAB support.';
    } else {
        session_regenerate_id(true);
        unset($_SESSION['admin_id'], $_SESSION['admin_name']);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        $destination = $user['role'] === 'grower'
            ? 'grower/dashboard.php'
            : 'index.php';
        header('Location: ' . url($destination));
        exit;
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | IFAB Flower Exchange</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="login-theme">
    <div class="login-screen">
        <header class="login-topbar">
            <a class="login-brand" href="<?= e(url()) ?>"><span>✿</span><strong>IFAB</strong><small>FLOWER EXCHANGE</small></a>
            <a class="login-home-link" href="<?= e(url()) ?>">Back to website <b>↗</b></a>
        </header>

        <main class="login-theme-grid">
            <section class="login-theme-art">
                <div class="login-art-copy">
                    <span class="login-kicker">GROW · CONNECT · BLOOM</span>
                    <h1>Flowers for<br><em>every moment.</em></h1>
                    <p>A considered marketplace for beautiful flowers, thoughtful growers and the people who bring them home.</p>
                    <span class="login-art-line"></span>
                    <small>THE BEAUTY OF EVERY SEASON</small>
                </div>
            </section>

            <section class="login-theme-panel">
                <span class="login-kicker">WELCOME BACK</span>
                <h2>Sign in to<br><em>your garden.</em></h2>
                <p class="login-subtitle">Enter your details to continue to IFAB.</p>

                <?php if ($error): ?><div class="theme-login-message"><?= e($error) ?></div><?php endif; ?>
                <?php if ($info): ?><div class="theme-login-message theme-login-info"><?= e($info) ?></div><?php endif; ?>

                <form method="post" class="login-theme-form" autocomplete="on">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <label>Email address<input type="email" name="email" required autocomplete="username" value="<?= e($email) ?>" placeholder="you@example.com"></label>
                    <label>Password<input type="password" name="password" required autocomplete="current-password" placeholder="Enter your password"></label>
                    <button type="submit">Login <span>↗</span></button>
                </form>

                <div class="login-theme-footer">
                    <span>New to IFAB?</span>
                    <a href="<?= e(url('register.php')) ?>">Create an account</a>
                </div>
            </section>
        </main>

        <footer class="login-bottom-bar"><span>© <?= date('Y') ?> IFAB Flower Exchange</span><span>For growers, buyers and flower people.</span></footer>
    </div>
</body>
</html>
