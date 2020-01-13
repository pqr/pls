<?php if ($linksHtml || $badgeMessagess): ?>
    <div class="list-top-buttons-div">
        <?= $linksHtml ?>

        <?php foreach ($badgeMessagess as $message): ?>
            <?= $message ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $filters ?>

<?= $table ?>
