<?php use Light\Template as t; ?>
<table>
    <tr>
        <td class="col-left">
            <table class="form">
                <?= t::c('SentDateTime') ?>
                <?= t::c('From') ?>
                <?= t::c('To') ?>
                <?= t::c('Cc') ?>
                <?= t::c('Bcc') ?>
                <?= t::c('ReplyTo') ?>
                <?= t::c('Subject') ?>
                <?= t::c('ContentType') ?>
            </table>
        </td>
        <td class="col-middle">
            <table class="form">
                <?= t::c('RetryCount') ?>
                <?= t::c('Report') ?>
            </table>
        </td>
    </tr>
</table>

<iframe src="?a=<?= \Light\Request::getModule() ?>&ps=GetBody&id=<?= t::_id() ?>"
        style="width: 100%; height: 550px"></iframe>