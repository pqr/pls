<?php if ($rightHtml = \Light\Response::getPageHeadRightHtml()): ?>
    <div id="page-head-right"><?= $rightHtml ?></div>
<?php endif; ?>
<h1 id="page-head">
    <?= \Light\Response::getPageHeadHtml() ?>
</h1>
