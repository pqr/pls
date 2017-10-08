<div id="tablelayoutdialog" class="display-none">
    <table>
        <tr>
            <td>
                <h3>Show <a href="#" id="tablelayoutdialog__linkall">all</a></h3>
            </td>

            <td>
                <h3>Hide <a href="#" id="tablelayoutdialog__linknone">all</a></h3>
            </td>

            <td>
                <h3>Layouts</h3>
            </td>
        </tr>

        <tr>
            <td>

            </td>
            <td>
                <input type="text" id="tablelayoutdialog__filter" placeholder="<?= \Light\Config::$lang === 'ru' ? 'Найти поле...' : 'Search...' ?>">
            </td>
            <td rowspan="2">

                <ul id="tablelayoutdialog__layoutsdefault">
                </ul>
                <div id="tablelayoutdialog__layoutsuser">
                </div>
                <div style="margin-top: 10px;">
                    <input type="text" id="tablelayoutdialog__newlayout">
                    <input type="button" value="Save" id="tablelayoutdialog__btnsave">
                </div>

            </td>
        </tr>

        <tr valign="top">

            <td>
                <ul id="tablelayoutdialog__showul"></ul>
            </td>

            <td>
                <ul id="tablelayoutdialog__hideul"></ul>
            </td>


        </tr>
    </table>
</div>
