<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
            <table class="filters">
                <tr>
                    <th><?= t::label('FilterName') ?></th>
                    <td><?= t::html('FilterName') ?></td>
                    <th><?= t::label('CreatedBy') ?></th>
                    <td><?= t::html('CreatedBy') ?></td>
                </tr>
                <tr>
                    <th><?= t::label('CreatedDateTimeFrom') ?></th>
                    <td><?= t::html('CreatedDateTimeFrom') ?> - <?= t::html('CreatedDateTimeUntil') ?></td>
                    <th><?= t::label('UserGroupID') ?></th>
                    <td><?= t::html('UserGroupID') ?></td>
                </tr>
                <tr>
                    <th></th>
                    <td><?= t::filtersButtons() ?></td>
                </tr>
            </table>
        </td>
        <td>
            <table class="filters">
                <tr>
                    <th><?= t::label('ModuleID') ?></th>
                    <td><?= t::html('ModuleID') ?></td>
                    <th><?= t::label('MasterID') ?></th>
                    <td><?= t::html('MasterID') ?></td>
                </tr>
                <tr>
                    <th><?= t::label('TableID') ?></th>
                    <td><?= t::html('TableID') ?></td>
                    <th><?= t::label('RecordID') ?></th>
                    <td><?= t::html('RecordID') ?></td>
                </tr>
                <tr>
                    <th><?= t::label('FieldID') ?></th>
                    <td colspan="3"><?= t::html('FieldID') ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>