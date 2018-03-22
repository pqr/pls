<?php use Light\Template as t; ?>
<form action="?a=ColorHint&p=save#ajax" style="width: 800px">
    <table class="form form-full">
        <?php for ($i = 1; $i <= 11; $i++): ?>
            <?= t::c('ColorHint' . $i) ?>
        <?php endfor ?>
    </table>
    <div class="formactions">
        <button type="submit" class="waves-effect btn bgcolor-btn-primary btn-save">Save Color Hints</button>
    </div>
</form>
