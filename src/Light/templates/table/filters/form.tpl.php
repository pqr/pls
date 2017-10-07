<div class="filters">
    <form id="<?= $tableName ?>-filters-form" name="<?= $tableName ?>" class="filters-form"
          data-tablename="<?= $tableName ?>" onsubmit="return false;"
          data-fields="<?= htmlspecialchars(json_encode($fields)) ?>"
          data-saved="<?= htmlspecialchars(json_encode($savedFilters)) ?>"
          data-default-sort="<?= htmlspecialchars(json_encode($defaultSort)) ?>"
          data-nosettings="<?= json_encode($noSettings) ?>"
          data-newsystemflag="<?= json_encode($newSystemFlag) ?>"
    >
        <?= $content ?>
    </form>
</div>
