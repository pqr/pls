<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= \Light\Config::$title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="i/icon/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="i/icon/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="i/icon/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="i/icon/manifest.json">
    <link rel="mask-icon" href="i/icon/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="i/icon/favicon.ico">
    <meta name="msapplication-config" content="i/icon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" type="text/css"
          href="lightpublic/css/Login.css?<?= filemtime(LIGHT_DIR_LIGHTPUBLIC . '/css/Login.css') ?>">

</head>
<body>
<div style="text-align: center; margin-top: 30px;">
    <div>
        <form action="<?= $formActionUrl ?>" method="post" onsubmit="document.getElementById('subm').disabled = true">

            <?php if (isset($LogoUrl)): ?>
                <img src="<?= $LogoUrl ?>" width="<?= $LogoWidth ?? '' ?>" height="<?= $LogoHeight ?? '' ?>" alt="">
                <?php if (isset($LogoText)): ?>
                    <p id="text" style="width: <?= $LogoWidth ?? '' ?>"><?= $LogoText ?></p>
                <?php endif; ?>
            <?php endif; ?>

            <p style="color: #b94a48"><?= $errorMsg ?></p>

            <p>
                <label for="username">Username</label><br>
                <input type="text"
                       name="username"
                       id="username"
                       tabindex="1"
                       value="<?= $_POST['username'] ?? '' ?>"
                    <?= ($errorField === 'username') ? 'class="error"' : '' ?>
                >
            </p>

            <p>
                <label for="password">Password</label><br>
                <input type="password"
                       name="password"
                       id="password"
                       tabindex="2"
                       value="<?= $_POST['password'] ?? '' ?>"
                    <?= ($errorField === 'password') ? 'class="error"' : '' ?>
                >
            </p>

            <p>
                <button id="subm" type="submit" tabindex="3">Login</button>
            </p>
        </form>
    </div>
</div>
</body>
</html>