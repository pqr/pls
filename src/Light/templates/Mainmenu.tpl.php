<?php

use Light\Link;
use Light\Mainmenu;
use Light\Module\UserModule\UserModule;
use Light\Module\UserSettings\UserSettings;
use Light\User;

$activeModule = \Light\Request::getModule();

?>
<div id="mainmenu">
    <div id="mainmenu__top">
        <div style="text-align: right">
            <a class="pin-menu-button"><i class="fa fa-thumb-tack"></i></a>
        </div>
        <?php if (date('m-d') === '03-07' && \Light\User::getFieldData('Sex') === 'f'): ?>
            <div style="margin: 0 auto 1em;">
                <?php
                $files = glob('lightpublic/i/march8_gifs/*.gif');
                $file = array_rand($files);
                ?>
                <img src="<?= $files[$file] ?>" style="max-width: 120px; max-height: 200px; cursor: pointer;" class="march8">
            </div>
        <?php else: ?>
            <div id="mainmenu__avatar">
                <?php /*<img src="lightpublic/i/santa-hat.png" class="santa-hat">*/ ?>
                <img class="avatar" src="<?= Mainmenu::getUserAvatarImgSrc(User::getId()) ?>">
                <input id="mainmenu__avatarinputfile" type="file" name="avatar">
            </div>
        <?php endif; ?>

        <div id="mainmenu__username">
            <?= $UserName ?>
        </div>

        <div id="mainmenu__usergroup">
            <?= htmlspecialchars(\Light\User::getGroupName()) ?>
            <a href="index.php?a=Logout#ajax-post" id="mainmenu__logout" title="Logout"><i
                        class="fa fa-sign-out"></i></a>
        </div>

        <input type="text" placeholder="Поиск по меню" id="mainmenu__search">

        <div style="text-align: left; margin: 2px 0 0 10px">
            <a href="index.php?a=Core&ps=MainmenuSettings#ajax" id="mainmenu__settings" class="note">
                Настройка меню
            </a>
        </div>
    </div>

    <div class="Mainmenu-Favorites">
        <?= $favoritesHtml ?>
    </div>

    <?php foreach ($sectionsHtml as $section => $sectionHtml): ?>

        <?php if ($section): ?>
            <div class="mainmenu__sectionhead" data-name="<?= htmlspecialchars($section) ?>">
                <?= $section ?>
            </div>
        <?php endif ?>
        <?= $sectionHtml ?>
    <?php endforeach; ?>
</div>

<?php if (isset($rates)): ?>

    <p><strong>CBRF rates:</strong> <a href="http://news.yandex.ru/quotes/1.html" target="_blank"
                                       style="font-size: 10px">link</a>
        <br><span class="note">(<?= $rates['Date'] ?>)</span>
    </p>

    <pre>
<p>EUR / USD: <?= $rates['CBRFEURUSD'] ?></p>
<p>USD / RUB: <?= $rates['CBRFUSDRUB'] ?></p>
<p>EUR / RUB: <?= $rates['CBRFEURRUB'] ?></p>
</pre>

<?php endif; ?>
