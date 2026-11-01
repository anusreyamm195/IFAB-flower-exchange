<?php
$pageTitle = 'About page videos';
require __DIR__ . '/_top.php';

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $youtubeUrl = trim($_POST['youtube_url'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';

        if ($title === '' || youtube_embed_url($youtubeUrl) === '') {
            flash('Enter a title and a valid YouTube URL.', 'error');
        } elseif ($id) {
            $pdo->prepare('UPDATE site_videos SET title=?, youtube_url=?, description=?, status=? WHERE id=?')
                ->execute([$title, $youtubeUrl, $description ?: null, $status, $id]);
            flash('About video updated.');
        } else {
            $pdo->prepare('INSERT INTO site_videos(title, youtube_url, description, status) VALUES(?,?,?,?)')
                ->execute([$title, $youtubeUrl, $description ?: null, $status]);
            flash('About video added.');
        }
        header('Location: ' . url('admin/site_videos.php'));
        exit;
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM site_videos WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('About video deleted.');
        header('Location: ' . url('admin/site_videos.php'));
        exit;
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM site_videos WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$videos = $pdo->query('SELECT * FROM site_videos ORDER BY created_at DESC')->fetchAll();
$flash = get_flash();
?>
<?php if ($flash): ?><p class="notice <?= e($flash[1] ?? 'success') ?>"><?= e($flash[0]) ?></p><?php endif; ?>
<div class="admin-columns">
    <section class="panel">
        <div class="panel-heading"><h2><?= $edit ? 'Edit video' : 'Add an About video' ?></h2></div>
        <p class="panel-help">Active videos appear in the public About section and play on that page.</p>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <label>Video title<input required name="title" value="<?= e($edit['title'] ?? '') ?>" placeholder="IFAB flower market"></label>
            <label>YouTube URL<input required type="url" name="youtube_url" value="<?= e($edit['youtube_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..."></label>
            <label>Description<textarea name="description" rows="4" placeholder="Optional description"><?= e($edit['description'] ?? '') ?></textarea></label>
            <label>Status<select name="status"><option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></label>
            <button class="btn btn-primary">Save video</button>
            <?php if ($edit): ?><a class="back-link" href="<?= url('admin/site_videos.php') ?>">Cancel edit</a><?php endif; ?>
        </form>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><span class="eyebrow">ABOUT PAGE</span><h2>Video library</h2></div><span class="status"><?= count($videos) ?> total</span></div>
        <div class="table-wrap"><table><thead><tr><th>Video</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($videos as $video): ?><tr><td><b><?= e($video['title']) ?></b><small class="subline"><?= e($video['youtube_url']) ?></small></td><td><?= e($video['status']) ?></td><td><a href="?edit=<?= (int)$video['id'] ?>">Edit</a><form method="post" class="inline-form" onsubmit="return confirm('Delete this video?')"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>"><button class="link-danger">Delete</button></form></td></tr><?php endforeach; ?>
        <?php if (!$videos): ?><tr><td colspan="3">No About videos yet.</td></tr><?php endif; ?></tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
