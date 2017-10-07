<?php
use Light\Template as t;

?>
<table class="filters">
    <?= t::c('TableName') ?>
    <?= t::c('CreatedBy') ?>
    <?= t::datesFromUntil('CreatedDateTime') ?>
    <?= t::c('RecordID') ?>
    <?= t::c('Query') ?>
    <?= t::c('JSON') ?>
    <?= t::filtersButtonsRow() ?>
</table>