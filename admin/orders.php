<?php
$pageTitle = 'Orders and bills';
require __DIR__ . '/_top.php';
$pdo = db();
$allowedStatuses = ['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($orderId > 0 && in_array($status, $allowedStatuses, true)) {
        $contextStmt = $pdo->prepare(
            "SELECT o.order_number, o.buyer_id, f.grower_id
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             JOIN flowers f ON f.id = oi.flower_id
             WHERE o.id = ? LIMIT 1"
        );
        $contextStmt->execute([$orderId]);
        $context = $contextStmt->fetch();

        if ($context) {
            $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $orderId]);
            if (in_array($status, ['confirmed', 'out_for_delivery', 'delivered'], true)) {
                $message = 'Order ' . $context['order_number'] . ' is now ' . str_replace('_', ' ', $status) . '.';
                $pdo->prepare("INSERT INTO notifications(user_id, title, message) VALUES(?, ?, ?)")
                    ->execute([(int)$context['buyer_id'], 'Order delivery update', $message]);
                if (!empty($context['grower_id'])) {
                    $pdo->prepare("INSERT INTO notifications(user_id, title, message) VALUES(?, ?, ?)")
                        ->execute([(int)$context['grower_id'], 'Order status updated by admin', $message]);
                }
            }
            flash('Order status updated.');
        }
    }
    header('Location: ' . url('admin/orders.php'));
    exit;
}

$rows = $pdo->query(
    "SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
            b.name AS buyer_name, b.email AS buyer_email,
            f.name AS flower_name, oi.quantity, oi.unit_price, f.unit,
            o.shipping_address, p.status AS payment_status,
            g.name AS grower_name
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     JOIN flowers f ON f.id = oi.flower_id
     JOIN users b ON b.id = o.buyer_id
     LEFT JOIN users g ON g.id = f.grower_id
     LEFT JOIN payments p ON p.order_id = o.id AND p.status = 'paid'
     ORDER BY o.created_at DESC, o.id DESC"
)->fetchAll();
$f = get_flash();
?>
<?php if ($f): ?><p class="notice"><?= e($f[0]) ?></p><?php endif; ?>
<section class="panel order-admin-panel">
    <div class="panel-heading"><div><span class="eyebrow">MARKETPLACE BILLING</span><h2>Buyer orders</h2></div><span class="status"><?= count($rows) ?> items</span></div>
    <div class="table-wrap"><table><thead><tr><th>Order</th><th>Buyer / address</th><th>Grower</th><th>Flower</th><th>Qty</th><th>Bill</th><th>Payment</th><th>Status</th><th>Update</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?><tr><td><b><?= e($row['order_number']) ?></b><small class="subline"><?= e(date('d M Y, H:i', strtotime($row['created_at']))) ?></small></td><td><?= e($row['buyer_name']) ?><small class="subline"><?= e($row['buyer_email']) ?></small><small class="subline"><?= nl2br(e($row['shipping_address'])) ?></small></td><td><?= e($row['grower_name'] ?: 'IFAB catalogue') ?></td><td><?= e($row['flower_name']) ?></td><td><?= (int)$row['quantity'] ?> <?= e($row['unit']) ?></td><td>₹<?= number_format((float)$row['total_amount'], 2) ?></td><td><span class="status"><?= e(ucfirst($row['payment_status'] ?: 'unpaid')) ?></span></td><td><span class="status"><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></span></td><td><form method="post" class="order-status-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= (int)$row['id'] ?>"><select name="status"><?php foreach ($allowedStatuses as $status): ?><option value="<?= e($status) ?>" <?= $row['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $status))) ?></option><?php endforeach; ?></select><button class="btn btn-small" type="submit">Save</button></form></td></tr><?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9">No orders yet.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/_bottom.php'; ?>
