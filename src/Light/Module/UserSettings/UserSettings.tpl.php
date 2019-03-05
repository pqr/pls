<?php

use Light\Config;
use Light\Template as t;

?>

<table class="form">
    <?= t::h3('Name') ?>
    <?= t::c('Email') ?>
    <?= t::c('InnerPhone') ?>
    <?= t::c('Mobile') ?>
    <?= t::c('Position') ?>
    <tr>
        <th>
            <?php if (Config::$lang === 'ru'): ?>
                День / месяц рождения
            <?php else: ?>
                Day / Month of Birth
            <?php endif; ?>
        </th>
        <td>
            <?= t::html('BirthDay') ?>.<?= t::html('BirthMonth') ?>
        </td>
    </tr>

    <tr>
        <td colspan="2" style="padding: 10px 0; background-color: transparent">
            <strong><?= t::label('EmailSign') ?></strong><br>
            <?= t::html('EmailSign') ?>
        </td>
    </tr>
</table>
