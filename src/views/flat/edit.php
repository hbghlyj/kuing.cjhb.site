<?php include 'src/views/partial/sidebar_button.php'; ?>
<div class="card fade-in-fwd">
    <div class="card-body">
        <?php if (!empty($saveError)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($saveError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <textarea name="markdown" class="form-control" rows="20" data-autoresize required><?= htmlspecialchars($markdown); ?></textarea>
            </div>
            <div class="form-group">
                <input type="file" name="images[]" multiple class="form-control-file">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="/page/<?= $slug ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
