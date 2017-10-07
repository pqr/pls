<form onsubmit="return false;">
    <?php include __DIR__ . '/CostSheetTabInside.tpl.php'; ?>
</form>

<div class="history-div">
    <?php if ($History != ''): ?>
        <?= $History ?>
    <?php endif; ?>
</div>

