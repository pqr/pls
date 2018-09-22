<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>

            <table class="form">
                <?php
                foreach ($_fieldset as $field) {
                    print t::c($field->key, $field->key === 'Name');
                }
                ?>
            </table>

            <?php
            if (isset($_subtables)) {
                foreach ($_subtables as $subtableName => $_) {
                    print t::subtable($subtableName);
                }
            }
            ?>

        </td>
    </tr>
</table>