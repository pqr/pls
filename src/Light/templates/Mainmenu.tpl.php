<?php

$activeModule = \Light\Request::getModule();

use Light\Ico; ?>
<div id="mainmenu">
    <div class="MainmenuTop">
        <div style="text-align: right">
            <a class="MainmenuPinBtn"><?= Ico::i('fa-thumbtack', 'fas') ?></i></a>
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
            <div class="MainmenuAvatar">
                <?php /* <img src="lightpublic/i/santa-hat.png" class="santa-hat"> */ ?>
                <?= $Avatar ?>
            </div>
<?php endif; ?>

        <div class="MainmenuUsername">
            <?= $UserName ?>
        </div>

        <div class="MainmenuUsergroup">
            <?= htmlspecialchars((string) \Light\User::getGroupName()) ?>
            <a href="index.php?a=Logout#ajax-post" class="MainmenuLogout" title="Logout"><i
                        class="fas fa-sign-out-alt"></i></a>
        </div>

        <input type="text" placeholder="Поиск по меню" class="MainmenuSearch">

        <div style="text-align: left; margin: 2px 0 0 10px">
            <a href="index.php?a=Core&ps=MainmenuSettings#ajax" class="note">
                Настройка меню
            </a>
        </div>
    </div>

    <div class="MainmenuFavorites">
        <?= $favoritesHtml ?>
    </div>

    <?php foreach ($sectionsHtml as $section => $sectionHtml): ?>

        <?php if ($section): ?>
            <div class="MainmenuSectionHead" data-name="<?= htmlspecialchars((string) $section) ?>">
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
