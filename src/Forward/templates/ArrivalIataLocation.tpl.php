<?php use Light\Template as t; ?>
<tr<?= t::title('ArrivalLocationName') ?> id="ArrivalIATA-tr"<?php if (isset($TrafficType) && $TrafficType == 'ocean') print ' class="display-none"'; ?>>
    <th><?= t::label('ArrivalLocationName') ?></th>
    <td><span title="Arrival IATA"><?= t::html('ArrivalIATA') ?></span> <span
            title="Arrival Location"><?= t::html('ArrivalLocationName') ?></span></td>
</tr>