<div class="buttonsbar">
    <?php foreach ($items as $item): ?>
    <a href="<?= $item['url'] ?>"<?= isset($item['linkCssClass']) ? ' class="' . $item['linkCssClass'] . '"' : '' ?>><?= $item['text'] ?></a>
    <?php endforeach; ?>
</div>