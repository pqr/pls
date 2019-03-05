<?php \Light\Template::includeTpl('tabsmenu.tpl.php', ['items' => $tabsmenuItems]) ?>
<?= $submenuHtml ?>

<div data-ajaxchangefieldurl="?a=<?= $module ?>&func=ChangeField&id="
     data-select2ajaxurl="?a=<?= $module ?>"
>
    <?= $content ?>
</div>
