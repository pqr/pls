<?php

use Light\Template as t;

?>

<form action="<?= $action ?>" method="post">
    <table class="form">
        <?= t::c('EmailTo') ?>
        <tr>
            <th>
            </th>
            <td><?= t::html('EmailSeparateTo') ?> <?= t::label('EmailSeparateTo') ?></td>
        </tr>
        <?= t::c('EmailCc') ?>
        <?= t::c('EmailReplyTo') ?>
        <?= t::c('EmailSubject') ?>
        <?php if (isset($_fieldset['EmailAttahements'])): ?>
            <?= t::c('EmailAttahements') ?>
        <?php elseif (isset($emptyFolderMsg)): ?>
            <tr>
                <th></th>
                <td><?= $emptyFolderMsg ?></td>
            </tr>
        <?php endif; ?>
        <?= t::c('EmailBody') ?>
    </table>
</form>

