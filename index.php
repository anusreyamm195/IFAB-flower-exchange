<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'A thriving marketplace for flowers';
$bodyClass = 'reference-theme';
$featured = db()->query("SELECT * FROM flowers WHERE status='active' ORDER BY created_at DESC LIMIT 3")->fetchAll();
$livePrices = [
    ['name' => 'Rose — Red', 'category' => 'Rose', 'price' => 6.40, 'unit' => 'IFAB average'],
    ['name' => 'Rose — White', 'category' => 'Rose', 'price' => 4.38, 'unit' => 'IFAB average'],
    ['name' => 'Rose — Yellow (Light)', 'category' => 'Rose', 'price' => 6.84, 'unit' => 'IFAB average'],
    ['name' => 'Rose — Yellow (Dark)', 'category' => 'Rose', 'price' => 8.71, 'unit' => 'IFAB average'],
    ['name' => 'Rose — Pink (Light)', 'category' => 'Rose', 'price' => 4.22, 'unit' => 'IFAB average'],
    ['name' => 'Rose — Pink (Medium)', 'category' => 'Rose', 'price' => 6.07, 'unit' => 'IFAB average'],
    ['name' => 'Rose — Pink (Dark)', 'category' => 'Rose', 'price' => 6.03, 'unit' => 'IFAB average'],
    ['name' => 'Rose — Orange', 'category' => 'Rose', 'price' => 6.39, 'unit' => 'IFAB average'],
    ['name' => 'Rose — Purple', 'category' => 'Rose', 'price' => 8.81, 'unit' => 'IFAB average'],
    ['name' => 'Rose — Cream', 'category' => 'Rose', 'price' => 5.45, 'unit' => 'IFAB average'],
];
$buyerHome = null;

if (!empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'buyer') {
    $buyerStmt = db()->prepare("SELECT name, email FROM users WHERE id = ? AND role = 'buyer' AND status = 'active'");
    $buyerStmt->execute([(int)$_SESSION['user_id']]);
    $buyerHome = $buyerStmt->fetch();
}

require __DIR__ . '/includes/header.php';
?>
<main>
    <section class="hero">
        <div class="hero-copy">
            <?php if ($buyerHome): ?><span class="eyebrow">WELCOME BACK, <?= e(strtoupper($buyerHome['name'])) ?></span><?php else: ?><span class="eyebrow">GROW &bull; CONNECT &bull; BLOOM</span><?php endif; ?>
            <h1>Where every bloom<br><em>finds its market.</em></h1>
            <p>A smarter flower exchange connecting growers and buyers with transparent listings, organised auctions and a thriving floral community.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= url('flowers.php') ?>">Explore marketplace <span>&rarr;</span></a>
                <?php if ($buyerHome): ?><a class="btn btn-outline" href="<?= url('buyer/account.php') ?>">My account</a><?php else: ?><a class="btn btn-outline" href="<?= url('login.php') ?>">Login to IFAB</a><a class="btn btn-outline" href="<?= url('register.php') ?>">Become a member</a><?php endif; ?>
            </div>
            <div class="hero-stats"><div><b>01</b><span>One connected marketplace</span></div><div><b>02</b><span>Transparent auction journeys</span></div><div><b>03</b><span>Grower-first ecosystem</span></div></div>
        </div>
        <div class="hero-art"><div class="hero-image"></div><div class="image-note"><span>THE BEAUTY OF EVERY SEASON</span><b>Freshness, connection<br>and opportunity.</b></div><div class="round-stamp">BLOOM<br>TOGETHER</div></div>
    </section>

    <section class="section intro"><div><span class="eyebrow">THE IFAB EXPERIENCE</span><h2>A better way to<br>trade what you grow.</h2></div><p>From discovering new varieties to participating in organised auctions, IFAB brings the flower trade closer together with a clear, accessible digital experience.</p></section>
    <section class="why-choose"><div class="why-choose-heading"><h2>Why Choose Us?</h2><p>IFAB offers its registered growers and buyers a trusted place to sell or buy flowers through transparent auctions and direct connections.</p></div><div class="why-choose-copy"><div class="why-choose-points"><div><b>&rarr;</b><strong>Direct exports of flowers from Bangalore</strong></div><div><b>&rarr;</b><strong>Middlemen totally eliminated</strong></div><div><b>&rarr;</b><strong>Assured flower quality and better value realization</strong></div><div><b>&rarr;</b><strong>Reduced flower damage through a reliable cold chain</strong></div><div><b>&rarr;</b><strong>Computerized auctions, faster billing and assured payments</strong></div><div><b>&rarr;</b><strong>Space for flower processing and a safe export cycle</strong></div><div><b>&rarr;</b><strong>Increased average unit value realization for flowers</strong></div></div></div></section>
    <section class="live-prices"><div class="live-prices-heading"><div><span class="eyebrow">IFAB MARKET REFERENCE</span><h2>Flower auction prices</h2><p>Original average prices published by IFAB International Flower Auction Bangalore.</p><a class="price-source-link" href="https://ifabindia.org/pricing/" target="_blank" rel="noopener">View original IFAB pricing source &rarr;</a></div><span class="live-indicator"><i></i> SOURCE</span></div><div class="live-price-grid"><?php foreach ($livePrices as $flower): ?><article class="live-price-card"><span><?= e($flower['category']) ?></span><strong><?= e($flower['name']) ?></strong><b>₹<?= number_format((float)$flower['price'], 2) ?><small> / <?= e($flower['unit']) ?></small></b></article><?php endforeach; ?></div></section>
    <section class="section feature-grid"><article class="feature-card"><span>01 / DISCOVER</span><h3>Explore the marketplace</h3><p>Browse flower varieties, availability and grower listings in one place.</p><a href="<?= url('flowers.php') ?>">Browse flowers &rarr;</a></article><article class="feature-card feature-green"><span>02 / PARTICIPATE</span><h3>Auctions made clearer</h3><p>Follow upcoming auctions and understand auction status and results.</p><a href="<?= url('auctions.php') ?>">View auctions &rarr;</a></article><article class="feature-card feature-image"><span>03 / CONNECT</span><h3>Grow together</h3><p>Build meaningful connections between growers and buyers.</p><a href="<?= url('register.php') ?>">Join the network &rarr;</a></article></section>
    <section class="section listing-section"><div class="section-heading"><div><span class="eyebrow">FRESH FROM THE MARKET</span><h2>Featured flowers</h2></div><a class="text-link" href="<?= url('flowers.php') ?>">View all flowers &rarr;</a></div><div class="flower-grid"><?php foreach ($featured as $f): ?><article class="flower-card"><div class="flower-photo" style="background-image:url('<?= e($f['image_url'] ?: 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=900') ?>')"></div><div class="flower-info"><span><?= e($f['category']) ?></span><h3><?= e($f['name']) ?></h3><p>₹<?= number_format((float)$f['price'], 2) ?> <small>/ <?= e($f['unit']) ?></small></p></div></article><?php endforeach; ?><?php if (!$featured): ?><div class="empty-state">Flower listings will appear here once the administrator adds them.</div><?php endif; ?></div></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
