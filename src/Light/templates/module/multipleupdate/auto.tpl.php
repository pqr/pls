<?php use Light\Template as t; ?>
<table class="multipleupdate form">
    <?php
    foreach ($_fieldset as $field) {
        print t::mu($field->key);
    }
    ?>
</table>