<?php
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'buyer') {
    header('Location: ' . url('login.php'));
    exit;
}

$buyerStmt = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'buyer' AND status = 'active'");
$buyerStmt->execute([(int)$_SESSION['user_id']]);
$buyer = $buyerStmt->fetch();
if (!$buyer) {
    session_unset();
    header('Location: ' . url('login.php'));
    exit;
}

$ordersStmt = db()->prepare(
    "SELECT o.order_number, o.total_amount, o.status, o.shipping_address, o.created_at,
            f.name AS flower_name, oi.quantity, oi.unit_price, f.unit
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     JOIN flowers f ON f.id = oi.flower_id
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC, o.id DESC LIMIT 10"
);
$ordersStmt->execute([(int)$buyer['id']]);
$orders = $ordersStmt->fetchAll();

$auctions = db()->query(
    "SELECT a.title, a.starts_at, a.base_price, a.status, f.name AS flower_name
     FROM auctions a LEFT JOIN flowers f ON f.id = a.flower_id
     WHERE a.status IN ('scheduled', 'live')
     ORDER BY a.starts_at ASC LIMIT 6"
)->fetchAll();

$notificationsStmt = db()->prepare("SELECT title, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notificationsStmt->execute([(int)$buyer['id']]);
$notifications = $notificationsStmt->fetchAll();

$pageTitle = 'My Account';
$bodyClass = 'reference-marketplace';
require __DIR__ . '/../includes/header.php';
?>
<main class="account-page">
    <section class="account-hero">
        <div><span class="eyebrow">MY IFAB ACCOUNT</span><h1><?= e($buyer['name']) ?></h1><p><?= e($buyer['email']) ?><?php if ($buyer['phone']): ?> &middot; <?= e($buyer['phone']) ?><?php endif; ?></p></div>
        <a class="btn btn-outline" href="<?= e(url('buyer/logout.php')) ?>">Logout</a>
    </section>

    <div class="account-grid">
        <section class="account-card account-orders">
            <div class="account-card-heading"><div><span class="eyebrow">BILLS & PURCHASES</span><h2>Recent purchases</h2></div><a class="text-link" href="<?= e(url('flowers.php')) ?>">Buy flowers &rarr;</a></div>
            <?php if ($orders): ?><div class="table-wrap"><table><thead><tr><th>Order</th><th>Flower</th><th>Total</th><th>Status</th></tr></thead><tbody><?php foreach ($orders as $order): ?><tr><td><b><?= e($order['order_number']) ?></b><small class="subline"><?= e(date('d M Y', strtotime($order['created_at']))) ?></small><small class="subline"><?= nl2br(e($order['shipping_address'])) ?></small></td><td><?= e($order['flower_name']) ?><small class="subline"><?= (int)$order['quantity'] ?> <?= e($order['unit']) ?></small></td><td>₹<?= number_format((float)$order['total_amount'], 2) ?></td><td><span class="status"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="account-empty">No purchases yet. Your bills will appear here after checkout.</p><?php endif; ?>
        </section>

        <section class="account-card">
            <div class="account-card-heading"><div><span class="eyebrow">FLOWER FLOOR</span><h2>Upcoming auctions</h2></div><a class="text-link" href="<?= e(url('auctions.php')) ?>">View all &rarr;</a></div>
            <?php if ($auctions): ?><div class="account-auctions"><?php foreach ($auctions as $auction): ?><div class="account-auction"><div><b><?= e($auction['title']) ?></b><small><?= e($auction['flower_name'] ?: 'Flower auction') ?> &middot; <?= e(date('d M Y, h:i A', strtotime($auction['starts_at']))) ?></small></div><span>₹<?= number_format((float)$auction['base_price'], 2) ?></span></div><?php endforeach; ?></div><?php else: ?><p class="account-empty">No upcoming auctions right now.</p><?php endif; ?>
        </section>
    </div>

    <?php if ($notifications): ?><section class="account-card account-notifications"><span class="eyebrow">UPDATES</span><h2>Recent messages</h2><?php foreach ($notifications as $notification): ?><p><b><?= e($notification['title']) ?></b><span><?= e($notification['message']) ?></span></p><?php endforeach; ?></section><?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
