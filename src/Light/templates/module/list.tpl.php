<?php if ($buttons): ?>
    <div class="list-top-buttons-div">
        <?php foreach ($buttons as $button): ?>

            <?php if ($button['text'] === 'Add New'): ?>
                <a href="<?= $button['url'] ?>" class="btn-floating btn waves-effect waves-light"
                   style="font-size: 30px">
                    +
                </a>
            <?php else: ?>
                <a href="<?= $button['url'] ?>"
                   class="btn-flat waves-effect waves-light<?= isset($button['linkCssClass']) ? ' ' . $button['linkCssClass'] : '' ?>"><?= $button['text'] ?></a>
            <?php endif ?>

        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $filters ?>

<div data-ajaxchangefieldurl="?a=<?= \Light\Request::getModule() ?>&func=ChangeField&id=">
    <?= $table ?>
</div>
