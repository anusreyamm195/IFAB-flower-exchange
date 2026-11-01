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

$pdo = db();
$error = '';
$message = '';
$orderId = (int)($_GET['order_id'] ?? $_SESSION['checkout_order_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_order') {
        $flowerId = (int)($_POST['flower_id'] ?? 0);
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        $address = trim($_POST['shipping_address'] ?? '');

        if ($flowerId < 1 || $quantity === false || $quantity < 1 || strlen($address) < 10) {
            $error = 'Please provide a valid quantity and complete delivery address.';
        } else {
            try {
                $pdo->beginTransaction();
                $flowerStmt = $pdo->prepare("SELECT * FROM flowers WHERE id = ? AND status = 'active' FOR UPDATE");
                $flowerStmt->execute([$flowerId]);
                $flower = $flowerStmt->fetch();
                if (!$flower) throw new RuntimeException('That flower listing is no longer available.');
                if ((int)$flower['quantity'] < $quantity) {
                    throw new RuntimeException('Only ' . (int)$flower['quantity'] . ' units are currently available.');
                }

                $total = (float)$flower['price'] * $quantity;
                $orderNumber = 'IFAB-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $stmt = $pdo->prepare(
                    "INSERT INTO orders(buyer_id, order_number, total_amount, shipping_address, status) VALUES(?, ?, ?, ?, 'pending')"
                );
                $stmt->execute([(int)$buyer['id'], $orderNumber, $total, $address]);
                $newOrderId = (int)$pdo->lastInsertId();
                $pdo->prepare("INSERT INTO order_items(order_id, flower_id, quantity, unit_price) VALUES(?, ?, ?, ?)")
                    ->execute([$newOrderId, $flowerId, $quantity, $flower['price']]);
                $pdo->prepare("UPDATE flowers SET quantity = quantity - ? WHERE id = ?")
                    ->execute([$quantity, $flowerId]);
                $pdo->commit();
                $_SESSION['checkout_order_id'] = $newOrderId;
                header('Location: ' . url('buyer/checkout.php') . '?order_id=' . $newOrderId);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = $e instanceof RuntimeException ? $e->getMessage() : 'We could not create your order.';
            }
        }
    }

    if ($action === 'pay_demo') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
        $orderStmt->execute([$orderId, (int)$buyer['id']]);
        $order = $orderStmt->fetch();
        if (!$order) {
            $error = 'Order not found.';
        } else {
            $paidStmt = $pdo->prepare("SELECT id FROM payments WHERE order_id = ? AND status = 'paid' LIMIT 1");
            $paidStmt->execute([$orderId]);
            if (!$paidStmt->fetch()) {
                $pdo->prepare("INSERT INTO payments(order_id, transaction_ref, amount, method, status, paid_at) VALUES(?, ?, ?, 'Demo payment', 'paid', NOW())")
                    ->execute([$orderId, 'DEMO-' . strtoupper(bin2hex(random_bytes(4))), $order['total_amount']]);
                $pdo->prepare("UPDATE orders SET status = 'pending' WHERE id = ?")->execute([$orderId]);

                $growerStmt = $pdo->prepare(
                    "SELECT DISTINCT f.grower_id, f.name, oi.quantity, f.unit
                     FROM order_items oi JOIN flowers f ON f.id = oi.flower_id
                     WHERE oi.order_id = ? AND f.grower_id IS NOT NULL LIMIT 1"
                );
                $growerStmt->execute([$orderId]);
                $grower = $growerStmt->fetch();
                if ($grower) {
                    $pdo->prepare("INSERT INTO notifications(user_id, title, message) VALUES(?, ?, ?)")
                        ->execute([(int)$grower['grower_id'], 'Paid order ready for acceptance', 'Order ' . $order['order_number'] . ' has been paid and is ready for acceptance.']);
                }
                $message = 'Demo payment successful. Your order has been sent to the grower.';
            } else {
                $message = 'This order has already been paid.';
            }
        }
    }
}

$order = null;
$orderItems = [];
$payment = null;
if ($orderId > 0) {
    $orderStmt = $pdo->prepare(
        "SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
         FROM orders o JOIN users u ON u.id = o.buyer_id
         WHERE o.id = ? AND o.buyer_id = ?"
    );
    $orderStmt->execute([$orderId, (int)$buyer['id']]);
    $order = $orderStmt->fetch();
    if ($order) {
        $itemsStmt = $pdo->prepare(
            "SELECT oi.*, f.name AS flower_name, f.unit FROM order_items oi JOIN flowers f ON f.id = oi.flower_id WHERE oi.order_id = ?"
        );
        $itemsStmt->execute([$orderId]);
        $orderItems = $itemsStmt->fetchAll();
        $paymentStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
        $paymentStmt->execute([$orderId]);
        $payment = $paymentStmt->fetch();
    }
}

$flower = null;
if (!$order) {
    $flowerId = (int)($_GET['flower_id'] ?? 0);
    $flowerStmt = $pdo->prepare("SELECT * FROM flowers WHERE id = ? AND status = 'active'");
    $flowerStmt->execute([$flowerId]);
    $flower = $flowerStmt->fetch();
    $quantity = max(1, (int)($_GET['quantity'] ?? 1));
}

$pageTitle = 'Checkout';
require __DIR__ . '/../includes/header.php';
?>
<main class="page-wrap checkout-page">
    <span class="eyebrow">IFAB CHECKOUT</span>
    <h1 class="page-title"><?= $order ? 'Your order bill' : 'Delivery details' ?></h1>
    <p class="lead">Enter your delivery address, review the bill, and complete your payment securely.</p>

    <?php if ($error): ?><p class="error order-feedback"><?= e($error) ?></p><?php endif; ?>
    <?php if ($message): ?><p class="notice order-feedback"><?= e($message) ?></p><?php endif; ?>

    <?php if (!$order && $flower): ?>
        <section class="checkout-card">
            <div class="checkout-product"><span class="eyebrow"><?= e($flower['category']) ?></span><h2><?= e($flower['name']) ?></h2><p>₹<?= number_format((float)$flower['price'], 2) ?> / <?= e($flower['unit']) ?></p></div>
            <form method="post" class="checkout-form">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create_order">
                <input type="hidden" name="flower_id" value="<?= (int)$flower['id'] ?>">
                <input type="hidden" name="quantity" value="<?= (int)$quantity ?>">
                <label>Delivery address<textarea name="shipping_address" required rows="5" placeholder="House / building, street, city, state, postal code"><?= e($_POST['shipping_address'] ?? '') ?></textarea></label>
                <div class="checkout-total"><span><?= (int)$quantity ?> × <?= e($flower['unit']) ?></span><strong>₹<?= number_format((float)$flower['price'] * $quantity, 2) ?></strong></div>
                <button class="btn btn-primary" type="submit">Review bill &rarr;</button>
            </form>
        </section>
    <?php elseif ($order): ?>
        <section class="checkout-card bill-card">
            <div class="bill-heading"><div><span class="eyebrow">BILL / <?= e($order['order_number']) ?></span><h2>Flower delivery bill</h2></div><span class="status"><?= e(ucfirst($order['status'])) ?></span></div>
            <p class="bill-address"><b>Deliver to</b><br><?= nl2br(e($order['shipping_address'])) ?></p>
            <div class="table-wrap"><table><thead><tr><th>Flower</th><th>Quantity</th><th>Unit price</th><th>Total</th></tr></thead><tbody>
                <?php foreach ($orderItems as $item): ?><tr><td><?= e($item['flower_name']) ?></td><td><?= (int)$item['quantity'] ?> <?= e($item['unit']) ?></td><td>₹<?= number_format((float)$item['unit_price'], 2) ?></td><td>₹<?= number_format((float)$item['unit_price'] * (int)$item['quantity'], 2) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <div class="checkout-total"><span>Grand total</span><strong>₹<?= number_format((float)$order['total_amount'], 2) ?></strong></div>
            <?php if (!$payment || $payment['status'] !== 'paid'): ?>
                <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="pay_demo"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><button class="btn btn-primary" type="submit">Pay securely &rarr;</button></form>
                <p class="demo-note">Your payment details are handled securely.</p>
            <?php else: ?>
                <p class="notice">Payment received on <?= e(date('d M Y, H:i', strtotime($payment['paid_at']))) ?>. The grower has been notified.</p>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <p class="error">The selected flower order could not be found.</p>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
