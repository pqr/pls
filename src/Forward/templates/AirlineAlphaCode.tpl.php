<?php use Light\Template as t; ?>
<tr id="AirlineAlphaCode-tr"<?php if (isset($TrafficType) && $TrafficType == 'ocean') print ' class="display-none"'; ?>>
    <th><label for="AirlineAlphaCodeInput">Airline</label></th>
    <td>
        <?php if (t::_state() !== 'view'): ?>
            <input type="text" style="width: 30px" maxlength="2" id="AirlineAlphaCodeInput"
                   value="<?= strlen((string) t::value('AirlineAlphaCode')) == 2 ? t::value('AirlineAlphaCode') : '' ?>">
        <?php endif; ?>
        <?= t::html('AirlineAlphaCode') ?>
    </td>
</tr>
 