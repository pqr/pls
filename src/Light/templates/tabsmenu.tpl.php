<div class="tabsmenu">

    <?php foreach ($items as $item): ?>
    <?= isset($item['active']) && $item['active'] ? '<span class="current">' : '' ?>
    <a href="<?= $item['url'] ?>"<?= isset($item['linkCssClass']) ? ' class="' . $item['linkCssClass'] . '"' : '' ?>><?= $item['text'] ?></a>
    <?= isset($item['active']) && $item['active'] ? '</span>' : '' ?>
    <?php endforeach; ?>

</div>

<div class="submenu-empty"></div>