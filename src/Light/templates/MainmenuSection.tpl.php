<?php
$activeModule = \Light\Request::getModule();
?>
<ul class="mainmenu__section"
    data-status="<?= $status ?>"<?= $status === 'closed' ? ' style="display: none"' : '' ?>>
    <?php foreach ($items as $module => ['text' => $text, 'title' => $title, 'url' => $url]): ?>

        <li class="mainmenu__li"<?= $title ? ' title="' . htmlspecialchars($title) . '"' : '' ?>>
            <a class="mainmenu__link<?= $module === $activeModule ? ' mainmenu__link_active' : '' ?>"
               href="<?= $url ?>">
                <?= $text ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>