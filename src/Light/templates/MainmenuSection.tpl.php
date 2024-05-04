<?php
$activeModule = \Light\Request::getModule();
?>
<ul class="MainmenuSection"
    data-status="<?= $status ?>"<?= $status === 'closed' ? ' style="display: none"' : '' ?>>
    <?php foreach ($items as $module => ['text' => $text, 'title' => $title, 'url' => $url]): ?>

        <li class="MeinmenuItem"<?= $title ? ' title="' . htmlspecialchars((string) $title) . '"' : '' ?>>
            <a class="MainmenuLink<?= $module === $activeModule ? ' MainmenuLink_active' : '' ?>"
               href="<?= $url ?>">
                <?= $text ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
