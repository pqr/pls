<ul class="tabs" data-active="<?= \Light\User::getParam('Console:tabs:') ?>">
    <li><a href="#tab-uploadhg">Upload hg bundle</a></li>
    <li><a href="#tab-sql">SQL</a></li>
    <li><a href="#tab-shell">Shell</a></li>
    <li><a href="#tab-backup">Backup</a></li>
    <li><a href="#tab-phpinfo">PHP Info</a></li>
</ul>

<div id="tab-uploadhg">
    <?php if (!\Light\Config::$hgPath): ?>
        <p class="bad">Config::$hgPath not defined</p>
    <?php else: ?>
        <form action="?a=Console&ps=uploadHgBundleProject" enctype="multipart/form-data" method="post">
            <?= \Light\Template::csrfHidden() ?>
            <h2><?= PROJECT_NAMESPACE ?></h2>
            <pre><?= $projectHgParent ?></pre>
            <p><a href="?a=Console&ps=downloadHgBundleProject">Download project bundle</a></p>
            <input type="file" name="projectbundle">
            <input type="submit" value="Upload, pull and update">
        </form>
        <?php if (isset($hgProjectResult)): ?>
            <pre><?= htmlspecialchars($hgProjectResult) ?></pre>
        <?php endif; ?>

        <form action="?a=Console&ps=uploadHgBundleLight" enctype="multipart/form-data" method="post">
            <?= \Light\Template::csrfHidden() ?>
            <h2>Light</h2>

            <pre><?= $lightHgParent ?></pre>
            <p><a href="?a=Console&ps=downloadHgBundleLight">Download light bundle</a></p>
            <input type="file" name="lightbundle">
            <input type="submit" value="Upload, pull and update">
        </form>
        <?php if (isset($hgLightResult)): ?>
            <pre><?= htmlspecialchars($hgLightResult) ?></pre>
        <?php endif; ?>

    <?php endif; ?>
</div>
<div id="tab-sql">
    <form id="sql-form" action="?a=Console&ps=sql" method="post" onsubmit="return false;">
        <textarea><?= isset($sql) ? htmlspecialchars($sql) : '' ?></textarea>
        <br><input type="submit" value="Run SQL Query">
        <pre class="result"></pre>
    </form>
</div>
<div id="tab-shell">
    <form id="shell-form" action="?a=Console&ps=shell" method="post" onsubmit="return false;">
        <textarea><?= isset($shellcmd) ? htmlspecialchars($shellcmd) : '' ?></textarea>
        <br><input type="submit" value="Execute Shell Command">
        <pre class="result"></pre>
    </form>
</div>
<div id="tab-backup">
    <?= $backupsHtml ?? '' ?>
</div>

<div id="tab-phpinfo">
    <?php if (\Light\User::getParam('Console:tabs:') === 'tab-phpinfo') { phpinfo(); } ?>
</div>
