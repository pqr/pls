<?php \Light\Template::includeTpl('tabsmenu.tpl.php', ['items' => $tabsmenuItems]) ?>
<?= $submenuHtml ?>

<div data-ajaxchangefieldurl="<?= $ajaxChangeFieldUrl ?>"
     data-select2ajaxurl="?a=<?= $module ?>"
>
    <?= $content ?>
</div>
