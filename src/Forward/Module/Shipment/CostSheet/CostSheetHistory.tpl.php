<?php
use Light\Template as t;

?>
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>CS <?= date('d.m.Y H:i') ?> (shipment <?= t::value('Name') ?></title>
    <?php foreach (\Light\Css::getSources() as $href): ?>
        <link rel="stylesheet" type="text/css" href="<?= $href ?>">
    <?php endforeach ?>
</head>
<body>

<h1 style="margin: 10px">Cost-sheet for shipment <?= t::html('Name') ?></h1>

<p style="margin: 10px">Created by <?= htmlspecialchars((string) $CreatedBy_raw) ?> @ <?= date('d.m.Y H:i') ?></p>
<hr>

<?php include __DIR__ . '/CostSheetHead.tpl.php' ?>
<?php include __DIR__ . '/CostSheetTabInside.tpl.php' ?>

</body>
</html>
