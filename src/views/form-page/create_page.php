<?php include 'src/views/partial/sidebar_button.php'; ?>

    <div class="card fade-in-fwd">
        <div class="card-body">
            <?= $form; ?>
            <?= isset($dataList) ? $dataList : ''; ?>
        </div>
    </div>
