<?php if ($buttons || $badgeMessagess): ?>
    <div class="list-top-buttons-div">
        <?php foreach ($buttons as $button): ?>

            <?php if (isset($button['text']) && $button['text'] === 'Add New'): ?>
                <a href="<?= $button['url'] ?>" class="btn-floating btn waves-effect"
                   style="font-size: 30px">
                    +
                </a>
            <?php elseif (isset($button['html'])): ?>
                <?= $button['html'] ?>
            <?php else: ?>
                <a href="<?= $button['url'] ?>"
                   class="btn-flat waves-effect<?= isset($button['linkCssClass']) ? ' ' . $button['linkCssClass'] : '' ?>"><?= $button['text'] ?></a>
            <?php endif ?>

        <?php endforeach; ?>

        <?php foreach ($badgeMessagess as $message): ?>
            <?= $message ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $filters ?>

<?= $table ?>
