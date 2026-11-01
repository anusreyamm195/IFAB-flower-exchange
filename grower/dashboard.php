<?php
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'grower') {
    header('Location: ' . url('login.php'));
    exit;
}

$stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'grower'");
$stmt->execute([$_SESSION['user_id']]);
$grower = $stmt->fetch();

if (!$grower || $grower['status'] !== 'active') {
    session_unset();
    header('Location: ' . url('login.php'));
    exit;
}

$flowerCount = (int) db()->query("SELECT COUNT(*) FROM flowers WHERE status = 'active'")->fetchColumn();
$auctionCount = (int) db()->query("SELECT COUNT(*) FROM auctions WHERE status IN ('scheduled', 'live')")->fetchColumn();
$listingMessage = '';
$listingError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_listing') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $unit = trim($_POST['unit'] ?? 'bunch') ?: 'bunch';
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $imagePath = '';
    $youtubeUrl = trim($_POST['youtube_url'] ?? '');
    $image = $_FILES['image'] ?? null;

    if (!$name || !$category || $price === false || $price < 0 || $quantity === false || $quantity < 0) {
        $listingError = 'Please enter a flower name, category, valid price and quantity.';
    } elseif ($image && $image['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif'
        ];
        $maxImageSize = 5 * 1024 * 1024;
        $imageInfo = ($image['error'] === UPLOAD_ERR_OK && $image['size'] <= $maxImageSize)
            ? @getimagesize($image['tmp_name'])
            : false;
        $mime = $imageInfo['mime'] ?? '';

        if (!$imageInfo || !isset($allowedTypes[$mime])) {
            $listingError = 'Please upload a valid JPG, PNG, WEBP or GIF image up to 5 MB.';
        } else {
            $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
            $uploadDirectory = __DIR__ . '/../assets/uploads/flowers';
            $target = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

            if (!is_dir($uploadDirectory) || !move_uploaded_file($image['tmp_name'], $target)) {
                $listingError = 'The image could not be uploaded. Please try again.';
            } else {
                $imagePath = 'assets/uploads/flowers/' . $filename;
            }
        }
    }

    if (!$listingError) {
        try {
            $stmt = db()->prepare(
                "INSERT INTO flowers(grower_id, name, category, price, unit, quantity, image_url, youtube_url, status)
                 VALUES(?, ?, ?, ?, ?, ?, ?, ?, 'active')"
            );
            $stmt->execute([(int)$grower['id'], $name, $category, $price, $unit, $quantity, $imagePath, $youtubeUrl]);
            $listingMessage = 'Your flower listing is live on the marketplace.';
            $flowerCount++;
        } catch (PDOException $e) {
            $listingError = 'We could not save this listing. Please try again.';
        }
    }
}

$orderMessage = '';
$orderError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['accept_order', 'update_order_status'], true)) {
    verify_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $requestedStatus = ($_POST['action'] ?? '') === 'accept_order' ? 'confirmed' : ($_POST['status'] ?? '');
    $allowedOrderStatuses = ['confirmed', 'out_for_delivery', 'delivered'];
    if (!in_array($requestedStatus, $allowedOrderStatuses, true)) {
        $orderError = 'That delivery status is not available.';
    }
    $orderStmt = db()->prepare(
        "SELECT o.buyer_id, o.order_number, o.status
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         JOIN flowers f ON f.id = oi.flower_id
         JOIN payments p ON p.order_id = o.id AND p.status = 'paid'
         WHERE o.id = ? AND f.grower_id = ?
         LIMIT 1"
    );
    $orderStmt->execute([$orderId, (int)$grower['id']]);
    $order = $orderStmt->fetch();

    $validTransition = $order && (
        ($order['status'] === 'pending' && $requestedStatus === 'confirmed') ||
        ($order['status'] === 'confirmed' && $requestedStatus === 'out_for_delivery') ||
        ($order['status'] === 'out_for_delivery' && $requestedStatus === 'delivered')
    );
    if ($orderError) {
        // Keep the validation message.
    } elseif (!$validTransition) {
        $orderError = 'This order is no longer waiting for acceptance.';
    } else {
        db()->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$requestedStatus, $orderId]);
        db()->prepare("INSERT INTO notifications(user_id, title, message) VALUES(?, ?, ?)")
            ->execute([(int)$order['buyer_id'], 'Delivery update', 'Order ' . $order['order_number'] . ' is now ' . str_replace('_', ' ', $requestedStatus) . '.']);
        $orderMessage = 'Order ' . $order['order_number'] . ' updated to ' . str_replace('_', ' ', $requestedStatus) . '.';
    }
}

$growerOrdersStmt = db()->prepare(
    "SELECT o.id, o.order_number, o.total_amount, o.shipping_address, o.status, o.created_at,
            u.name AS buyer_name, u.email AS buyer_email,
            f.name AS flower_name, oi.quantity, oi.unit_price, f.unit
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     JOIN flowers f ON f.id = oi.flower_id
     JOIN users u ON u.id = o.buyer_id
     JOIN payments p ON p.order_id = o.id AND p.status = 'paid'
     WHERE f.grower_id = ?
     ORDER BY o.created_at DESC, o.id DESC"
);
$growerOrdersStmt->execute([(int)$grower['id']]);
$growerOrders = $growerOrdersStmt->fetchAll();

$growerNotificationsStmt = db()->prepare(
    "SELECT title, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
);
$growerNotificationsStmt->execute([(int)$grower['id']]);
$growerNotifications = $growerNotificationsStmt->fetchAll();

$pageTitle = 'Seller Dashboard';
$bodyClass = 'reference-marketplace';
require __DIR__ . '/../includes/header.php';
?>

<main class="buyer-dashboard">
    <section class="buyer-welcome">
        <a class="btn btn-outline buyer-logout" href="<?= e(url('buyer/logout.php')) ?>">Log out</a>
        <span class="eyebrow">YOUR IFAB SELLER SPACE</span>
        <h1>Welcome, <?= e($grower['name']) ?>!</h1>
        <p class="buyer-email">Signed in as: <?= e($grower['email']) ?></p>
        <p>Manage your flower business and connect with buyers through IFAB.</p>
        <a class="btn btn-primary" href="<?= e(url('flowers.php')) ?>">View marketplace &rarr;</a>
    </section>

    <section class="buyer-stats">
        <article class="buyer-stat">
            <span>Active marketplace listings</span>
            <strong><?= $flowerCount ?></strong>
        </article>
        <article class="buyer-stat">
            <span>Upcoming and live auctions</span>
            <strong><?= $auctionCount ?></strong>
        </article>
    </section>

    <section class="buyer-actions">
        <article class="buyer-action">
            <span class="eyebrow">MARKETPLACE</span>
            <h2>View flower listings</h2>
            <p>Review current flowers, prices and availability in the marketplace.</p>
            <a class="btn btn-primary" href="<?= e(url('flowers.php')) ?>">Browse flowers &rarr;</a>
        </article>
        <article class="buyer-action">
            <span class="eyebrow">AUCTIONS</span>
            <h2>Track auctions</h2>
            <p>See scheduled and live auctions available to the IFAB community.</p>
            <a class="btn btn-primary" href="<?= e(url('auctions.php')) ?>">Explore auctions &rarr;</a>
        </article>
    </section>

    <section class="order-panel">
        <div class="panel-heading"><div><span class="eyebrow">SELLER ORDERS</span><h2>Buyer bills and requests</h2></div></div>
        <?php if ($orderMessage): ?><p class="notice order-feedback"><?= e($orderMessage) ?></p><?php endif; ?>
        <?php if ($orderError): ?><p class="error order-feedback"><?= e($orderError) ?></p><?php endif; ?>
        <?php if ($growerOrders): ?>
            <div class="table-wrap"><table><thead><tr><th>Order</th><th>Buyer</th><th>Flower</th><th>Qty</th><th>Bill</th><th>Status</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($growerOrders as $order): ?><tr><td><b><?= e($order['order_number']) ?></b><small class="subline"><?= e(date('d M Y', strtotime($order['created_at']))) ?></small></td><td><?= e($order['buyer_name']) ?><small class="subline"><?= e($order['buyer_email']) ?></small><small class="subline"><?= nl2br(e($order['shipping_address'])) ?></small></td><td><?= e($order['flower_name']) ?></td><td><?= (int)$order['quantity'] ?> <?= e($order['unit']) ?></td><td>₹<?= number_format((float)$order['total_amount'], 2) ?></td><td><span class="status"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span></td><td><?php if ($order['status'] === 'pending'): ?><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="accept_order"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><button class="btn btn-small" type="submit">Accept</button></form><?php elseif ($order['status'] === 'confirmed'): ?><form method="post" class="order-status-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_order_status"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><input type="hidden" name="status" value="out_for_delivery"><button class="btn btn-small" type="submit">Out for delivery</button></form><?php elseif ($order['status'] === 'out_for_delivery'): ?><form method="post" class="order-status-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_order_status"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><input type="hidden" name="status" value="delivered"><button class="btn btn-small" type="submit">Mark delivered</button></form><?php else: ?>Complete<?php endif; ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php else: ?><p class="order-empty">No buyer orders for your listings yet.</p><?php endif; ?>
    </section>

    <?php if ($growerNotifications): ?>
    <section class="order-panel notification-panel">
        <div class="panel-heading"><div><span class="eyebrow">INBOX</span><h2>Buyer and admin messages</h2></div></div>
        <?php foreach ($growerNotifications as $notification): ?><p class="notification-item"><b><?= e($notification['title']) ?></b><span><?= e($notification['message']) ?></span></p><?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="seller-listing-panel">
        <div class="seller-listing-intro">
            <span class="eyebrow">SELL YOUR FLOWERS</span>
            <h2>Add a new marketplace listing</h2>
            <p>Share your fresh flowers with buyers. Once submitted, the active listing appears in the admin catalogue and on the homepage.</p>
        </div>

        <?php if ($listingMessage): ?><p class="notice seller-notice"><?= e($listingMessage) ?></p><?php endif; ?>
        <?php if ($listingError): ?><p class="error seller-error"><?= e($listingError) ?></p><?php endif; ?>

        <form method="post" class="seller-listing-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_listing">
            <label>Flower name<input required name="name" placeholder="e.g. Garden Rose" value="<?= e($_POST['name'] ?? '') ?>"></label>
            <label>Category<input required name="category" placeholder="e.g. Roses" value="<?= e($_POST['category'] ?? '') ?>"></label>
            <label>Price (₹)<input required type="number" min="0" step="0.01" name="price" placeholder="0.00" value="<?= e($_POST['price'] ?? '') ?>"></label>
            <label>Unit<input name="unit" value="<?= e($_POST['unit'] ?? 'bunch') ?>" placeholder="bunch"></label>
            <label>Quantity<input required type="number" min="0" step="1" name="quantity" placeholder="0" value="<?= e($_POST['quantity'] ?? '') ?>"></label>
            <label class="seller-image-field">Flower image <span>(optional, JPG/PNG/WEBP/GIF up to 5 MB)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label>
            <label>YouTube process video <span>(optional direct YouTube link)</span><input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= e($_POST['youtube_url'] ?? '') ?>"></label>
            <button class="btn btn-primary" type="submit">Publish flower listing &rarr;</button>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
