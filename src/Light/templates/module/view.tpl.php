<?php \Light\Template::includeTpl('tabsmenu.tpl.php', ['items' => $tabsmenuItems]) ?>
<?php \Light\Template::includeTpl('submenu.tpl.php', ['items' => $submenuItems]) ?>

<div data-ajaxchangefieldurl="?a=<?= \Light\Request::getModule() ?>&func=ChangeField&id=">
    <?= $content ?>
</div>
