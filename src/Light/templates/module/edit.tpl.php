<?php \Light\Template::includeTpl('tabsmenu.tpl.php', ['items' => $tabsmenuItems]) ?>
<?php \Light\Template::includeTpl('submenu.tpl.php', ['items' => $submenuItems]) ?>

<form action="<?= $formActionUrl ?>" id="edit-form" name="edit-form" onsubmit="return false;" method="post"
      data-fields="<?= htmlspecialchars(json_encode($fields)) ?>"
      data-ajaxchangefieldurl="?a=<?= \Light\Request::getModule() ?>&func=ChangeField&id="
      data-select2ajaxurl="?a=<?= \Light\Request::getModule() ?>"
>
    <?= $content ?>
    <?= $formactions ?>
</form>