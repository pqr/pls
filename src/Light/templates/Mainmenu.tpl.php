<?php
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
                <img src="<?= $files[$file] ?>" style="width: 120px; cursor: pointer;" class="march8">
            </div>
        <?php else: ?>
            <div id="mainmenu__avatar">
                <!--            <img src="lightpublic/i/santa-hat.png" class="santa-hat">-->
                <img class="avatar" src="index.php?a=Core&ps=GetUserAvatar&nouserrefreshtime=1">
                <input id="mainmenu__avatarinputfile" type="file" name="avatar">
            </div>
        <?php endif; ?>

        <div id="mainmenu__username" title="Username"><?= htmlspecialchars(\Light\User::getName()) ?></div>
        <div id="mainmenu__usergroup" title="Usergroup"><?= htmlspecialchars(\Light\User::getGroupName()) ?></div>
        <div>
            <a href="index.php?a=Logout#ajax-post">Logout</a>
        </div>
    </div>

    <?php foreach ($sections as $sectionText => $sectionParams): ?>

        <?php if ($sectionText): ?>
            <div class="mainmenu__sectionhead" data-name="<?= htmlspecialchars($sectionText) ?>">
                <?= $sectionText ?>
            </div>
        <?php endif ?>

        <ul class="mainmenu__section"
            data-status="<?= $sectionParams['status'] ?>"<?= $sectionParams['status'] === 'closed' ? ' style="display: none"' : '' ?>>
            <?php foreach ($sectionParams['items'] as $module): ?>

                <li class="mainmenu__li">
                    <a class="mainmenu__link<?= $module === $activeModule ? ' mainmenu__link_active' : '' ?>"
                       href="index.php?a=<?= $module ?><?= isset($badges[$module]['urlParams']) ? '&' . http_build_query($badges[$module]['urlParams']) : '' ?>">
                        <?= \Light\ModuleRegister::getModuleName($module) ?> <?= isset($badges[$module]) ? $badges[$module]['html'] : '' ?>
                    </a>
                </li>

            <?php endforeach; ?>
        </ul>

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
