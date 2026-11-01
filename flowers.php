<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Flower Marketplace';
$bodyClass = 'reference-marketplace';
$q = trim($_GET['q'] ?? '');
$stmt = db()->prepare(
    "SELECT f.*
     FROM flowers f
     WHERE f.status = 'active' AND (f.name LIKE ? OR f.category LIKE ?)
     ORDER BY f.created_at DESC"
);
$stmt->execute(["%$q%", "%$q%"]);
$flowers = $stmt->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<main class="page-wrap">
    <span class="eyebrow">DISCOVER THE COLLECTION</span>
    <h1 class="page-title">Flower marketplace</h1>
    <p class="lead">Explore varieties and listings from the IFAB network.</p>
    <form class="search-form">
        <input name="q" value="<?= e($q) ?>" placeholder="Search flowers or categories">
        <button class="btn btn-dark">Search</button>
    </form>
    <div class="flower-grid">
        <?php foreach ($flowers as $f): ?>
            <article class="flower-card">
                <div class="flower-photo" style="background-image:url('<?= e($f['image_url'] ?: 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=900') ?>')"></div>
                <div class="flower-info">
                    <span><?= e($f['category']) ?> · <?= e($f['quantity']) ?> available</span>
                    <h3><?= e($f['name']) ?></h3>
                    <p>₹<?= number_format((float)$f['price'], 2) ?> <small>/ <?= e($f['unit']) ?></small></p>
                    <?php if (!empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'buyer'): ?>
                        <form method="get" action="<?= e(url('buyer/checkout.php')) ?>" class="buy-form">
                            <input type="hidden" name="flower_id" value="<?= (int)$f['id'] ?>">
                            <label>Quantity<input type="number" name="quantity" min="1" max="<?= (int)$f['quantity'] ?>" value="1" <?= (int)$f['quantity'] < 1 ? 'disabled' : '' ?>></label>
                            <button class="btn btn-primary" type="submit" <?= (int)$f['quantity'] < 1 ? 'disabled' : '' ?>>Buy flowers &rarr;</button>
                        </form>
                    <?php elseif (empty($_SESSION['user_id'])): ?>
                        <a class="btn btn-outline buy-link" href="<?= e(url('login.php')) ?>">Sign in to buy</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$flowers): ?><div class="empty-state">No matching flowers found.</div><?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
