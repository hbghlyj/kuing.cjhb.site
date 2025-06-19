<div class="container-fluid mt-3">
    <?php if($this->msg->display()) : ?>
        <?php echo $this->msg->display(); ?>
    <?php endif; ?>
</div>
<div class="login-container text-center">
        <?php 
            if (file_exists('json/logo.png')) {
                echo '<a href="/doc.php"><img id="logo" src="json/logo.png?'.time().'" alt="logo" class="img-fluid mb-3"></a>';
            } else {
                echo '<a href="/doc.php"><h3>'.TITLE.' <i class="fa fa-code" aria-hidden="true"></i></h3></a>';
            }
        ?>
        <div class="card fade-in-fwd">
        <div class="card-body shadow-sm">
            <?= $form; ?>
            <a href="/member.php?mod=logging&action=login&viewlostpw=1" class="text-muted"><?= $t->trans('I lost my password') ?></a>
        </div>
    </div>
</div>