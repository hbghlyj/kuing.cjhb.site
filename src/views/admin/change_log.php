<?php include 'src/views/partial/sidebar_button.php'; ?>

<div class="card fade-in-fwd">
    <div class="card-body">
        <h3 class="mb-4"><?= $t->trans('Change log'); ?></h3>
        <input class="form-control mb-4" id="change-log-search" type="text" placeholder="<?= $t->trans('Search'); ?>">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered table-dark">
                <thead>
                    <tr>
                        <th scope="col"><?= $t->trans('Date'); ?></th>
                        <th scope="col"><?= $t->trans('Action'); ?></th>
                        <th scope="col"><?= $t->trans('Username'); ?></th>
                        <th scope="col"><?= $t->trans('Page'); ?></th>
                    </tr>
                </thead>
                <tbody id="change-log-table">
                    <?php
                    if (!empty($changes)) {
                        $changes = array_reverse($changes);
                        $grouped = [];
                        foreach ($changes as $entry) {
                            $day = substr($entry['date'], 0, 10);
                            $grouped[$day][] = $entry;
                        }
                        foreach ($grouped as $day => $entries) {
                            echo '<tr class="table-primary"><th colspan="4"><small>' . $day . '</small></th></tr>';
                            foreach ($entries as $entry) {
                                $link = '/page/' . $entry['slug'];
                                echo '<tr>';
                                echo '<td><small>' . $entry['date'] . '</small></td>';
                                echo '<td><small>' . $t->trans(ucfirst($entry['action'])) . '</small></td>';
                                echo '<td><small>' . $entry['username'] . '</small></td>';
                                echo '<td><small><a href="' . $link . '">' . $entry['slug'] . '</a></small></td>';
                                echo '</tr>';
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
