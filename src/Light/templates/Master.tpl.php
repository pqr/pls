<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Page-Enter" content="progid:DXImageTransform.Microsoft.Fade(duration='0.05', overalap='0.05')">
    <meta http-equiv="Page-Exit" content="progid:DXImageTransform.Microsoft.Fade(duration='0.05', overalap='0.05')">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \Light\Response::getFullTitle() ?></title>
    <?php foreach (\Light\Css::getSources() as $href): ?>
        <link rel="stylesheet" type="text/css" href="<?= $href ?>">
    <?php endforeach ?>
    <?php if (Light\Config::$lang === 'ru'): ?>
        <style type="text/css">
            a.ajax-input:empty:after {
                content: "изменить...";
            }
        </style>
    <?php endif; ?>
    <link rel="apple-touch-icon" sizes="180x180" href="i/icon/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="i/icon/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="i/icon/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="i/icon/manifest.json">
    <link rel="mask-icon" href="i/icon/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="i/icon/favicon.ico">
    <meta name="msapplication-config" content="i/icon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <?php if (Light\Config::$okmeterKey): ?>
        <script async="async" type="text/javascript"
                src="https://rum.okmeter.ru/static/js/rum_inline.js?okmRUMkey=<?= Light\Config::$okmeterKey ?>"></script>
    <?php endif; ?>

    <?php if (\Light\Config::$rollbar && isset(\Light\Config::$rollbar['access_token_js'])): ?>
        <script>
            var _rollbarConfig = {
                accessToken: "<?= \Light\Config::$rollbar['access_token_js'] ?>",
                captureUncaught: true,
                captureUnhandledRejections: false,
                payload: {
                    environment: "<?= \Light\Config::$rollbar['environment'] ?? 'prodution'?>",
                    person: {
                        id: <?= \Light\User::getId(); ?>,
                        username: <?= json_encode(\Light\User::getLogin()); ?>
                    },
                    client: {
                        javascript: {
                            source_map_enabled: true,
                            code_version: "<?= \Light\ApplicationAbstract::getVersion() ?>",
                            guess_uncaught_frames: true
                        }
                    }
                }
            };

        </script>
        <script src="lightpublic/js/rollbar.umd.min.js"></script>
    <?php elseif (\Light\Config::$sentry): ?>
        <script src="lightpublic/js/raven.min.js"></script>
        <script>
            Raven.config("<?= \Light\Config::$sentry['dns_js'] ?>").install();
            Raven.setRelease("<?= \Light\ApplicationAbstract::getVersion() ?>");
            Raven.setUserContext({
                id: <?= \Light\User::getId(); ?>,
                username: <?= json_encode(\Light\User::getLogin()); ?>
            });
        </script>
    <?php endif; ?>
</head>
<body>
<?php if (extension_loaded('newrelic')) {
    echo newrelic_get_browser_timing_header();
} ?>
<script>
    window._lightModule = <?= json_encode(\Light\Request::getModule()) ?>;
    window._lightCsrfToken = <?= json_encode($_SESSION['csrf_token']) ?>;
    window._lightDateFormat = <?= json_encode(\Light\Config::$defaultDateFormat) ?>;
    window._lightDateTimeFormat = <?= json_encode(\Light\Config::$defaultDateTimeFormat) ?>;
    window._lightLang = <?= json_encode(\Light\Config::$lang) ?>;
    <?php if (isset($_REQUEST['scrollTop'])): ?>
     window._lightScrollTop = <?= (int)$_REQUEST['scrollTop'] ?>;
    <?php endif; ?>
    window._lightPageParams = <?= json_encode(\Light\Response::getJsData()) ?>;
</script>

<?php if (\Light\Response::isMainmenuEnabled()): ?>
<div class="yui3-g <?= \Light\User::getParam("global-menu-unfix") ? 'global-menu-unfix' : '' ?>" id="global-layout">

    <div class="hideshow-menu-button">
        <i class="fa fa-bars fa-2x"></i>
    </div>

    <div class="yui3-u global-menu" id="global-menu">
        <?= \Light\Mainmenu::getMainmenuHtml() ?>
    </div>
    <div class="yui3-u" id="global-content">
        <?php endif; ?>

        <?= \Light\Template::parseTpl('pagehead.tpl.php') ?>

        <div id="flash-messages">
            <?php foreach (\Light\Session::flashGetAll() as $flash): ?>
                <div
                        class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?>"><?= $flash['msg'] ?></div>
            <?php endforeach; ?>
        </div>

        <?= $output ?>


        <?php if (\Light\Response::isMainmenuEnabled()): ?>
    </div>
</div>
<?php endif; ?>

<?= \Light\Response::getAdditionalHtml() ?>

<?php foreach (\Light\Js::getSources() as $src): ?>
    <script type="text/javascript" src="<?= $src ?>"></script>
<?php endforeach ?>

<script>
    __lightmetrics =
    <?= json_encode([
        'working time' => \Light\ApplicationAbstract::getWorkingTime(),
        'queriesCount' => \Light\Db::getQueriesCount(),
        'queriesLog' => \Light\Db::getQueriesLog(),
        'queriesTime' => \Light\Db::getQueriesTime(),
        'peak memory usage' => (memory_get_peak_usage(true) / 1024) . ' Kb',
        'included files' => count(get_included_files())
    ], JSON_PRETTY_PRINT) ?>
</script>
<?php if (extension_loaded('newrelic')) {
    echo newrelic_get_browser_timing_footer();
} ?>
</body>
</html>
