<?php

use Light\Config;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?= $title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2"/>

    <!-- Section CSS -->
    <!-- jQuery UI (REQUIRED) //cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css -->
    <link rel="stylesheet" type="text/css" href="lightpublic/css/jquery-ui.min.css">

    <!-- elFinder CSS (REQUIRED) -->
    <link rel="stylesheet" type="text/css" href="lightpublic/elfinder/css/elfinder.min.css">
    <link rel="stylesheet" type="text/css" href="lightpublic/elfinder/css/theme.css">


    <!-- Section JavaScript -->
    <!-- jQuery and jQuery UI (REQUIRED)
    //cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js
    //cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js
    -->
    <script src="lightpublic/elfinder/js/jquery.min.js"></script>
    <script src="lightpublic/elfinder/js/jquery-ui.min.js"></script>

    <!-- elFinder JS (REQUIRED) -->
    <script src="lightpublic/elfinder/js/elfinder.min.js"></script>

    <!-- Extra contents editors (OPTIONAL) -->
    <script src="lightpublic/elfinder/js/extras/editors.default.min.js"></script>

    <!-- elFinder initialization (REQUIRED) -->
    <script type="text/javascript" charset="utf-8">
        // Documentation for client options:
        // https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
        $(document).ready(function () {
            $('#elfinder').elfinder({
                customData: {
                    "csrf_token": <?= json_encode($_SESSION['csrf_token']) ?>
                },
                url: "<?= $apiUrl ?>",
                soundPath: "lightpublic/elfinder/sounds/",
                lang: "<?= Config::$lang ?>",
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
