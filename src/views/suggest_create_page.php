<?php
/**
 * This file is part of the DocPHT project.
 *
 * @author Valentino Pesce
 * @copyright (c) Valentino Pesce <valentino@iltuobrand.it>
 * @copyright (c) Craig Crosby <creecros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
?>

<div class="jumbotron text-center fade-in-fwd">
    <h1><?= $t->trans('Page not found') ?></h1>
    <p><?= $t->trans('The page you are looking for does not exist:') ?> <strong><?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p><?= $t->trans('You can create this page:') ?></p>
    <a href="/page/create?topic=<?= urlencode($topic) ?>&filename=<?= urlencode($filename) ?>" class="btn btn-primary"><?= $t->trans('Create new page') ?></a>
    <hr>
    <p><?= $t->trans('Alternatively, you can search for the page:') ?></p>
    <form action="/page/search" method="post">
        <div class="input-group mb-3">
            <input type="text" class="form-control" name="search" placeholder="<?= $t->trans('Search for...') ?>" value="<?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?>">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="submit"><?= $t->trans('Search') ?></button>
            </div>
        </div>
    </form>
</div>
