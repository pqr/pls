<?php use Light\Template as t; ?>
<table class="form">
    <?= t::c('CreatedBy') ?>
    <?= t::c('CreatedDateTime') ?>
    <?= t::c('ShortMessage') ?>
    <?= t::c('WorkTime') ?>
</table>

<div class="subtableblock">
    <h2>Report</h2>
    <div>
        <?= t::html('Report') ?>
    </div>
</div>

