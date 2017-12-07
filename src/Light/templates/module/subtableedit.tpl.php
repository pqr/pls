<div id="<?= $name ?>-subtable" class="subtable-div" data-subtablename="<?= $name ?>"
     data-fields="<?= htmlspecialchars($fieldsJson) ?>">

    <?php if ($allowAdd || $allowPaste): ?>
    <table>
        <tr>
            <td>
                <?php endif; ?>

                <?= $content ?>

                <?php if ($allowAdd || $allowPaste): ?>
                <p class="subtable-add-p">

                    <?php if ($allowAdd): ?>
                        <a href="#" class="subtable-add-link btn-flat waves-effect">
                            <i class="fa fa-plus"></i>
                            <?= $textAddLink ?></a>
                    <?php endif; ?>

                    <?php if ($allowPaste): ?>
                        <a href="#" class="subtable-paste-link btn-flat waves-effect">
                            <i class="fa fa-clipboard"></i>
                            <?= \Light\Config::$lang === 'ru' ? 'Вставить' : 'Paste' ?></a>
                    <?php endif; ?>
                </p>
            </td>
        </tr>
    </table>
<?php endif; ?>

    <input type="hidden" name="<?= $name ?>Subtable<?= $postfixName ?? '' ?>[ids]" id="<?= $name ?>-subtable-ids" value="<?= $ids ?>">
    <?php if ($allowDelete): ?>
        <input type="hidden" name="<?= $name ?>Subtable<?= $postfixName ?? '' ?>[delrows]" id="<?= $name ?>-subtable-delrows">
    <?php endif; ?>

</div>
