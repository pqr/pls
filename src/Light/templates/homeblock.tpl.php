<table class="home-block"<?php if ($width): ?> width="<?= $width ?>"<?php endif; ?>>
    <tr>
        <th class="home-block-th <?= $cssClass ?>">
            <div>
                <?= $title ?>
                <?php if ($count !== null): ?>(<?= $count ?>)<?php endif; ?>
            </div>
        </th>
    </tr>
    <tr>
        <td class="content">
            <?php if ($description): ?><p><?= $description ?></p><?php endif; ?>
            <?php if ($informationFor): ?><p class="note"><?= $informationFor ?></p><?php endif; ?>
            <div class="viewalllink"><?= $viewAllLink ?></div>
            <?= $table ?>
        </td>
    </tr>
</table>
