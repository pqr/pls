<h1 id="page-head">
    <?php if ($rightHtml = \Light\Response::getPageHeadRightHtml()): ?>
        <div id="page-head-right" class="page-head-for-unfix"><?= $rightHtml ?></div><?php endif; ?>
    <?= \Light\Response::getPageHeadHtml() ?>
</h1>
