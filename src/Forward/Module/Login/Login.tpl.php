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
    <link rel="shortcut icon" href="favicon.ico">
    <meta name="msapplication-config" content="i/icon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" type="text/css"
          href="lightpublic/css/Login.css?<?= filemtime(LIGHT_DIR_LIGHTPUBLIC . '/css/Login.css') ?>">

</head>
<body>


<div style="position: absolute; top: 20%; left: 50%; margin-left: -244px; width: 488px;">
    <div>
        <p style="color: #b94a48"><?= $errorMsg ?></p>
        <form action="<?= $formActionUrl ?>" method="post" onsubmit="document.getElementById('subm').disabled = true">
            <table style="background-color: #00A6D6; border-radius: 10px; color: white;">
                <tr valign="top">
                    <td style="padding: 20px 50px 20px 20px;">
                        <img src="<?= $LogoUrl ?>" width="<?= $LogoWidth ?>" height="<?= $LogoHeight ?>" alt="">
                    </td>
                    <td id="form-td" style="padding: 20px;">
                        <p id="username-p" style="margin-top: 0; text-align: left;">
                            <label for="username">Login:</label><br>
                            <input type="text"
                                   name="username"
                                   id="username"
                                   tabindex="1"
                                   value="<?= $_POST['username'] ?? '' ?>"
                                <?= ($errorField === 'username') ? 'class="error"' : '' ?>
                            >
                        </p>

                        <p id="password-p" style="text-align: left;">
                            <label for="password">Password:</label><br>
                            <input type="password"
                                   name="password"
                                   id="password"
                                   tabindex="2"
                                   value="<?= $_POST['password'] ?? '' ?>"
                                <?= ($errorField === 'password') ? 'class="error"' : '' ?>
                            >
                        </p>

                        <p style="text-align: left;">
                            <button id="subm" type="submit" tabindex="3">Enter</button>
                        </p>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

</body>
</html>