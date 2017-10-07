<?php use Light\Template as t; ?>
<tr<?= t::title('DepartureLocationName') ?> id="DepartureIATA-tr"<?php if (isset($TrafficType) && $TrafficType == 'ocean') print ' class="display-none"'; ?>>
    <th><?= t::label('DepartureLocationName') ?></th>
    <td><span title="Departure IATA"><?= t::html('DepartureIATA') ?></span> <span
            title="Departure Location"><?= t::html('DepartureLocationName') ?></span></td>
</tr>