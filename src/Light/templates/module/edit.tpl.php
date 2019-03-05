<?php \Light\Template::includeTpl('tabsmenu.tpl.php', ['items' => $tabsmenuItems]) ?>
<?= $submenuHtml ?>

<form action="<?= $formActionUrl ?>" id="edit-form" name="edit-form" onsubmit="return false;" method="post"
      data-fields="<?= htmlspecialchars(json_encode($fields)) ?>"
      data-ajaxchangefieldurl="?a=<?= $module ?>&func=ChangeField&id="
      data-select2ajaxurl="?a=<?= $module ?>"
>
    <?= $content ?>
    <?= $formactions ?>
</form>
