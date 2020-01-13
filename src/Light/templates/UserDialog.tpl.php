<?php

use Light\Config;
use Light\Ico;
use Light\Mainmenu;
use Light\Misc;
use Light\Template as t;
use Light\Translate;
use Light\User;

?>

<table class="form">
    <tr>
        <th><?= User::avatarImgHtml(t::value('ID')) ?></th>
        <td style="background-color: transparent; vertical-align: middle">
            <h3 style="font-size: 25px; margin: 0"><?= t::text('Name') ?></h3>
            <?php if (isset($Idle)): ?>
            <div class="small" style="margin-top: 5px">Последняя активность <?= $Idle ?></div>
            <?php endif; ?>
        </td>
    </tr>

    <?= t::c('Email') ?>

    <?php if (t::value('InnerPhone')): ?>
        <?= t::c('InnerPhone') ?>
    <?php endif; ?>

    <?php if (t::value('Mobile')): ?>
        <?= t::c('Mobile') ?>
    <?php endif; ?>

    <?php if (t::value('Position')): ?>
        <?= t::c('Position') ?>
    <?php endif; ?>

    <?php if (t::value('BirthDay') && t::value('BirthMonth')): ?>
        <tr>
            <th>
                <?php if (Config::$lang === 'ru'): ?>
                    День рождения
                <?php else: ?>
                    Birth Day
                <?php endif; ?>
            </th>
            <td>
                <?= t::html('BirthDay') ?>
                <?php if (Config::$lang === 'ru'): ?>
                    <?= Translate::monthRu(t::value('BirthMonth'), true) ?>
                <?php else: ?>
                    <?= t::html('BirthMonth') ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
    <?php if (isset($UserSettingsLink)): ?>
        <tr>
            <th></th>
            <td style="background-color: transparent;"><?= $UserSettingsLink ?></td>
        </tr>
    <?php endif; ?>
</table>

<?= $UserVacationsHtml ?>
<?= $UserDialogAdditionalHtml ?>
