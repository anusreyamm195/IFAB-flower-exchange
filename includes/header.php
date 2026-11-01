<?php $pageTitle = $pageTitle ?? 'IFAB Flower Exchange'; $bodyClass = $bodyClass ?? ''; ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | IFAB</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head><body class="<?= e($bodyClass) ?>">
<header class="site-header">
<div class="site-utility"><span>✿ &nbsp;Fresh Blooms. Fair Auctions. A Greener Tomorrow.</span><span>&bull; &nbsp;Trusted by Growers &amp; Buyers Across India</span></div>
<div class="site-header-inner">
<a class="brand" href="<?= url() ?>"><span class="brand-mark">✿</span><span>IFAB<small>FLOWER EXCHANGE</small></span></a>
<nav><a href="<?= url() ?>">Home</a><a href="<?= url('flowers.php') ?>">Marketplace</a><a href="<?= url('auctions.php') ?>">Auctions</a><a href="<?= url('about.php') ?>">About</a><a href="<?= url('contact.php') ?>">Contact</a></nav>
<div class="header-actions">
<?php if (!empty($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['buyer', 'grower'], true)): ?>
<a class="account-icon" href="<?= url(($_SESSION['user_role'] ?? '') === 'grower' ? 'grower/dashboard.php' : 'buyer/account.php') ?>" aria-label="Open my account" title="My account"><span><?= e(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1))) ?></span></a>
<?php if (($_SESSION['user_role'] ?? '') === 'grower'): ?><a class="text-link" href="<?= url('grower/dashboard.php') ?>">Dashboard</a><?php endif; ?>
<a class="text-link" href="<?= url('buyer/logout.php') ?>">Logout</a>
<?php else: ?>
<a class="header-login" href="<?= url('login.php') ?>">Log In</a><a class="header-signup" href="<?= url('register.php') ?>">Sign Up</a>
<?php endif; ?>
</div></div></header>
