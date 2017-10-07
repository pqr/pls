<h2 class="section">Access Rights</h2>

<?php
if ($_state != 'view') {
    $items = [];
    if (\Light\Request::getModule() == 'User') {
        $items[] = ['url' => '#-1', 'text' => 'All Inerit'];
    }

    $items[] = ['url' => '#0', 'text' => 'All Deny'];
    $items[] = ['url' => '#1', 'text' => 'All Read'];
    $items[] = ['url' => '#2', 'text' => 'All Write'];

    \Light\Template::includeTpl('buttonsbar.tpl.php', ['items' => $items]);
}
?>

<table class="form">
    <?php foreach ($sections as $sectionTitle => $items): ?>
    <tr>
        <th><strong><?= $sectionTitle ?></strong></th>
    </tr>

    <?php foreach ($items as $moduleName => $tdata): ?>
        <tr>
            <th><?= $tdata['title'] ?></th>
            <td style="width: 100px"><?= $tdata['html'] ?></td>
            <td style="width: 400px">
                <?php if (count($tdata['special']) > 0): ?>
                <ul class="special-rights">
                    <?php foreach ($tdata['special'] as $specialTdata): ?>
                    <li><?= $specialTdata['title'] ?>: <?= $specialTdata['html'] ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>

    <?php endforeach; ?>
</table>
