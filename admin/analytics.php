<?php
$pageTitle = 'Analytics';
require __DIR__ . '/_top.php';
$pdo = db();
$months = $pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') label, COUNT(*) total FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at), MONTH(created_at)")->fetchAll();
$cats = $pdo->query("SELECT category, COUNT(*) total FROM flowers GROUP BY category ORDER BY total DESC LIMIT 8")->fetchAll();
$statusCounts = $pdo->query("SELECT status, COUNT(*) total FROM orders GROUP BY status ORDER BY total DESC")->fetchAll();
$paid = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'")->fetchColumn();
$pending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed','out_for_delivery')")->fetchColumn();
$delivered = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
?>
<div class="metric-grid">
    <article class="metric-card metric-highlight"><span>Collected revenue</span><strong>₹<?= number_format($paid, 2) ?></strong><small>Paid transactions</small></article>
    <article class="metric-card"><span>Orders in delivery flow</span><strong><?= $pending ?></strong><small>Pending, accepted or out for delivery</small></article>
    <article class="metric-card"><span>Delivered orders</span><strong><?= $delivered ?></strong><small>Completed deliveries</small></article>
</div>
<div class="analytics-grid">
    <section class="panel"><span class="eyebrow">GROWTH</span><h2>New member registrations</h2><canvas id="memberChart" height="180"></canvas></section>
    <section class="panel"><span class="eyebrow">CATALOGUE MIX</span><h2>Flower categories</h2><canvas id="categoryChart" height="180"></canvas></section>
    <section class="panel"><span class="eyebrow">FULFILMENT</span><h2>Order status</h2><canvas id="orderChart" height="180"></canvas></section>
</div>
<script>
new Chart(document.getElementById('memberChart'),{type:'line',data:{labels:<?= json_encode(array_column($months,'label')) ?>,datasets:[{label:'Registrations',data:<?= json_encode(array_map('intval',array_column($months,'total'))) ?>,borderColor:'#507b5c',backgroundColor:'rgba(80,123,92,.12)',fill:true,tension:.35}]},options:{responsive:true,plugins:{legend:{display:false}}}});
new Chart(document.getElementById('categoryChart'),{type:'doughnut',data:{labels:<?= json_encode(array_column($cats,'category')) ?>,datasets:[{data:<?= json_encode(array_map('intval',array_column($cats,'total'))) ?>,backgroundColor:['#557c61','#d9a7a0','#d7bd80','#8da99a','#b7a0bd','#9eaece','#c9a48a','#9baf72']}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
new Chart(document.getElementById('orderChart'),{type:'doughnut',data:{labels:<?= json_encode(array_map(fn($row)=>ucwords(str_replace('_',' ',$row['status'])),$statusCounts)) ?>,datasets:[{data:<?= json_encode(array_map('intval',array_column($statusCounts,'total'))) ?>,backgroundColor:['#d7bd80','#557c61','#d99a9c','#8da99a','#b7a0bd','#9eaece']}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
</script>
<?php require __DIR__ . '/_bottom.php'; ?>
