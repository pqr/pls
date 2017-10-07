<?php use Light\Template as t; ?>
<div class="submenu filters">
    <form action="?a=Autolaunch&class=<?= $className ?>&run=1" class="well form-inline" id="autolaunch-form" onsubmit="return false;"
          method="post">
        <?= t::csrfHidden() ?>
        <table class="filters">
            <tr>
                <?php if (isset($_fieldset)): ?>
                    <?php foreach ($_fieldset as $field): ?>
                            <th><?= t::label($field->key) ?></th>
                            <td><?= t::html($field->key) ?></td>
                    <?php endforeach; ?>
                <?php endif; ?>
                <td style="padding-left: 15px;">
                    <button type="submit" class="btn bgcolor-btn-primary" title="Ctrl + Enter"> Run</button>
                </td>
            </tr>
        </table>
    </form>
</div>
<div style="margin-top: 10px;"><?= $description ?></div>

<h2>History</h2>
<div>
    <?= $historyTableHtml ?>
</div>