<?php

use Light\Config;
use Light\Mainmenu;
use Light\Misc;
use Light\Template as t;

?>
<table class="form">
    <tr>
        <th></th>
        <td style="background-color: transparent">
            <div style="display: flex; justify-content: space-between; align-items: center">
                <div>
                    <h3><?= t::text('Name') ?></h3>
                </div>
                <div style="text-align: right">
                    <img class="avatar" src="<?= Mainmenu::getUserAvatarImgSrc(t::value('ID')) ?>">
                </div>
            </div>
        </td>
    </tr>
    <?= t::c('Email') ?>
    <?= t::c('InnerPhone') ?>
    <?= t::c('Mobile') ?>
    <?= t::c('Position') ?>
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
                    <?= Misc::getRusMonthName(t::value('BirthMonth'), true) ?>
                <?php else: ?>
                    <?= t::html('BirthMonth') ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
</table>
