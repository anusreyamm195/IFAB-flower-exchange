<?php
require_once __DIR__.'/config/config.php';
$pageTitle = 'Join IFAB';
$bodyClass = 'reference-marketplace';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $role = $_POST['role'] ?? 'buyer';
    $phone = trim($_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($name && $email && in_array($role, ['grower', 'buyer'], true) && strlen($pass) >= 8) {
        try {
            $s = db()->prepare("INSERT INTO users(name,email,phone,role,password_hash) VALUES(?,?,?,?,?)");
            $s->execute([$name, $email, $phone, $role, password_hash($pass, PASSWORD_DEFAULT)]);
            $msg = 'Registration successful. Your account is pending administrator approval.';
        } catch (PDOException $e) {
            $msg = 'That email may already be registered.';
        }
    } else {
        $msg = 'Please complete all fields. Password must be at least 8 characters.';
    }
}

require __DIR__.'/includes/header.php';
?>
<main class="auth-page register-page">
    <div class="auth-visual">
        <span class="eyebrow">JOIN THE NETWORK</span>
        <h1>Bring your<br><em>flowers to life.</em></h1>
        <p>Join a connected community where growers find buyers and buyers discover the beauty of every season.</p>
        <div class="auth-visual-note"><span>GROW TOGETHER</span><b>A smarter marketplace<br>for every season.</b></div>
    </div>

    <form method="post" class="auth-card" autocomplete="on">
        <span class="eyebrow">CREATE YOUR ACCOUNT</span>
        <h2>Welcome to IFAB.</h2>
        <p>Choose how you want to take part in the flower exchange.</p>

        <?php if ($msg): ?><p class="notice"><?= e($msg) ?></p><?php endif; ?>

        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Full name<input required name="name" value="<?= e($_POST['name'] ?? '') ?>"></label>
        <label>Email<input required type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Phone<input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
        <fieldset class="role-choice">
            <legend>Account type</legend>
            <label><input type="radio" name="role" value="buyer" <?= ($_POST['role'] ?? 'buyer') === 'buyer' ? 'checked' : '' ?>> <span>Buyer<small>Discover and purchase flowers</small></span></label>
            <label><input type="radio" name="role" value="grower" <?= ($_POST['role'] ?? '') === 'grower' ? 'checked' : '' ?>> <span>Seller / Grower<small>List flowers and reach buyers</small></span></label>
        </fieldset>
        <label>Password<input required type="password" minlength="8" name="password"></label>
        <button class="btn btn-primary full">Create your account &rarr;</button>
        <div class="login-bottom">Already a member? <a href="<?= e(url('login.php')) ?>">Sign in</a></div>
    </form>
</main>
<?php require __DIR__.'/includes/footer.php'; ?>
