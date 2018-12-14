<?php

use Light\Config;

$isLangRu = Config::$lang === 'ru'
?>
<div id="tablelayoutdialog">
    <table>
        <tr>
            <td>
                <h3><?= $isLangRu ? 'Отображаемые' : 'Visible' ?> <a href="#"
                                                                     id="tablelayoutdialog__linkall"><?= $isLangRu ? 'все' : 'all' ?></a>
                </h3>
            </td>

            <td>
                <h3><?= $isLangRu ? 'Скрытые' : 'Hidden' ?> <a href="#"
                                                               id="tablelayoutdialog__linknone"><?= $isLangRu ? 'все' : 'all' ?></a>
                </h3>
            </td>

            <td>
                <h3><?= $isLangRu ? 'Сохранённые наборы' : 'Saved Layouts' ?></h3>
            </td>
        </tr>

        <tr>
            <td>

            </td>
            <td>
                <input type="text" id="tablelayoutdialog__filter"
                       placeholder="<?= $isLangRu ? 'Найти поле...' : 'Search...' ?>">
            </td>
            <td rowspan="2">

                <ul id="tablelayoutdialog__layoutsdefault">
                </ul>
                <div id="tablelayoutdialog__layoutsuser">
                    <?= $userLayouts ?>
                </div>
                <div style="margin-top: 10px;">
                    <input type="text" id="tablelayoutdialog__newlayout">
                    <p>
                        <input type="button" value="<?= $isLangRu ? 'Сохранить набор' : 'Save layout' ?>"
                           id="tablelayoutdialog__btnsave">
                    </p>
                </div>

            </td>
        </tr>

        <tr valign="top">

            <td>
                <ul class="tablelayoutdialog__fieldsul" id="tablelayoutdialog__showul"></ul>
            </td>

            <td>
                <ul class="tablelayoutdialog__fieldsul" id="tablelayoutdialog__hideul"></ul>
            </td>


        </tr>
    </table>
</div>
