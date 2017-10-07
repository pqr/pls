<?php use Light\Template as t; ?>
<table class="filters">
    <?php
    foreach ($_fieldset as $field) {
        print t::c($field->key);
    }
    ?>
    <tr>
        <th></th>
        <td><?= t::filtersButtons() ?></td>
    </tr>
</table>

