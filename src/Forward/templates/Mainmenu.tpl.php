<?php
//Для начала выведем обычное меню из движка
include LIGHT_DIR_LIGHT . '/templates/Mainmenu.tpl.php';

//Теперь добавим курсы банка Citadele
?>
<p style="margin-top: 20px"><strong>Citadele</strong> <a href="http://www.citadele.ee/ru/rates/" target="_blank" style="font-size: 10px">link</a></p>

<?php
//Загрузим из базы курсов Citadele ближайшие курсы EUR/USD, USD/RUB
$eurUsd = \Light\Db::getRow("SELECT ValidDate, BuyingForEUR, SellingForEUR FROM currencyrate_citadele WHERE Name = 'USD' ORDER BY ValidDate DESC LIMIT 1");
$usdRub = \Light\Db::getRow("SELECT ValidDate, BuyingForUSD, SellingForUSD FROM currencyrate_citadele WHERE Name = 'RUB' ORDER BY ValidDate DESC LIMIT 1");
?>
<table id="citadelerates">
    <tr>
        <td></td>
        <td>Покупка</td>
        <td>Продажа</td>
    </tr>
    <tr title="<?= isset($eurUsd['ValidDate']) ? \Light\Field\FieldDate::beautify($eurUsd['ValidDate']) : '' ?>">
        <td>EUR / USD</td>
        <td><?= $eurUsd['SellingForEUR'] ?? '' ?></td>
        <td><?= $eurUsd['BuyingForEUR'] ?? '' ?></td>
    </tr>
    <tr title="<?= isset($usdRub['ValidDate']) ? \Light\Field\FieldDate::beautify($usdRub['ValidDate']) : '' ?>">
        <td>USD / RUB</td>
        <td><?= $usdRub['BuyingForUSD'] ?? '' ?></td>
        <td><?= $usdRub['SellingForUSD'] ?? '' ?></td>
    </tr>
</table>
