<?php if ($items): ?>
    <div class="submenu">
        <?php foreach ($items as $item): ?>
            <?php if (isset($item['html'])): ?>
                <?= $item['html'] ?>
            <?php else: ?>
                <a href="<?= $item['url'] ?>"
                    <?= isset($item['blank']) && $item['blank'] ? ' target="_blank"' : '' ?>>
                    <?= $item['text'] ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
        <div style="clear: left;"></div>
    </div>
<?php endif; ?>