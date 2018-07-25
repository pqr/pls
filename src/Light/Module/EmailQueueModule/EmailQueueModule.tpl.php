<?php use Light\Template as t; ?>
<table class="form">
    <?= t::c('PID') ?>
    <?= t::c('From') ?>
    <?= t::c('To') ?>
    <?= t::c('Cc') ?>
    <?= t::c('Bcc') ?>
    <?= t::c('ReplyTo') ?>
    <?= t::c('Subject') ?>
    <?= t::c('Attachements') ?>
    <?= t::c('ContentType') ?>
    <?= t::c('RetryCount') ?>
</table>

<iframe src="?a=<?= \Light\Request::getModule() ?>&id=<?= t::_id() ?>&func=ShowBody" style="width: 100%; height: 550px"></iframe>
