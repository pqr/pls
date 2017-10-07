<?php \Light\Template::includeTpl('tabsmenu.tpl.php', ['items' => $tabsmenuItems]) ?>
<?php \Light\Template::includeTpl('submenu.tpl.php', ['items' => $submenuItems]) ?>

<form action="<?= $formActionUrl ?>" id="edit-form" name="edit-form" onsubmit="return false;" method="post"
      data-fields="<?= htmlspecialchars(json_encode($fields)) ?>">
    <?= $content ?>
    <?= $formactions ?>
</form>