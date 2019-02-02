<?php

use Light\Config;

$isLangRu = Config::$lang === 'ru'
?>
<div class="MainmenuSettings">
    <div>Перетяните пункты меню из правого списа в левый, чтобы добавить в "Избранное".</div>
    <div>Избранные пункты будут отображаться в меню слева выше остальных.</div>
    <table>
        <tr valign="top">

            <td>
                <h3><?= $isLangRu ? 'Избранные' : 'Favorites' ?></h3>
                <ul class="MainmanueSettings-Favorites">
                    <?php foreach ($favoriteItems as $module => $moduleName): ?>
                        <li class="MainmenuSettings-Item" data-module="<?= $module ?>"><?= $moduleName ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>

            <td>
                <h3><?= $isLangRu ? 'Все пункты меню' : 'All menu items' ?></h3>
                <ul class="MainmanueSettings-AllItems">
                    <?php foreach ($allItems as $module => $moduleName): ?>
                        <li class="MainmenuSettings-Item" data-module="<?= $module ?>"><?= $moduleName ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>


        </tr>
    </table>

</div>
