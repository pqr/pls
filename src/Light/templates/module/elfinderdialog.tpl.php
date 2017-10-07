<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2"/>

    <!-- Section CSS -->
    <!-- jQuery UI (REQUIRED) -->
    <link rel="stylesheet" type="text/css" href="lightpublic/elfinder/jquery-ui-full/jquery-ui-1.12.1.min.css">

    <!-- elFinder CSS (REQUIRED) -->
    <link rel="stylesheet" type="text/css" href="lightpublic/elfinder/css/elfinder.min.css">
    <link rel="stylesheet" type="text/css" href="lightpublic/elfinder/css/theme.css">


    <!-- Section JavaScript -->
    <!-- jQuery and jQuery UI (REQUIRED) -->
    <script src="lightpublic/elfinder/jquery-ui-full/jquery-2.2.4.min.js"></script>
    <script src="lightpublic/elfinder/jquery-ui-full/jquery-ui-1.12.1.min.js"></script>

    <!-- elFinder JS (REQUIRED) -->
    <script src="lightpublic/elfinder/js/elfinder.min.js"></script>

    <script src="lightpublic/elfinder/js/i18n/elfinder.ru.js"></script>

    <!-- elFinder initialization (REQUIRED) -->
    <script type="text/javascript" charset="utf-8">
        // Documentation for client options:
        // https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
        $(document).ready(function () {
            $('#elfinder').elfinder({
                customData: {
                    "csrf_token": <?= json_encode($_SESSION['csrf_token']) ?>
                },
                url: '?a=<?= $module ?>&id=<?= $id ?>&elfinder=api',
                lang: 'ru',
                rememberLastDir: false,
                height: window.innerHeight - 10
            });
        });
    </script>
</head>
<body style="margin: 0">

<!-- Element where elFinder will be created (REQUIRED) -->
<div id="elfinder"></div>

</body>
</html>
