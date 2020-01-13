<?php

use Light\Template as t;

?>

<table class="form">
    <?= t::c('EmailAccountID') ?>
    <?= t::c('EmailTo') ?>
    <tr>
        <th>
        </th>
        <td><?= t::html('EmailSeparateTo') ?> <?= t::label('EmailSeparateTo') ?></td>
    </tr>
    <?= t::c('EmailCc') ?>
    <?= t::c('EmailReplyTo') ?>
    <?= t::c('EmailSubject') ?>
    <?php if (isset($_fieldset['EmailAttachementsFromFolder'])): ?>
        <?= t::c('EmailAttachementsFromFolder') ?>
    <?php elseif (isset($emptyFolderMsg)): ?>
        <tr>
            <th></th>
            <td><?= $emptyFolderMsg ?></td>
        </tr>
    <?php endif; ?>
    <?php if ($allowAttachementsManual): ?>
        <tr>
            <th>
            </th>
            <td>
                <input type="file" id="ManualAttachements" name="ManualAttachements[]" multiple>
            </td>
        </tr>
    <?php endif; ?>
    <?= t::c('EmailBody') ?>
</table>

