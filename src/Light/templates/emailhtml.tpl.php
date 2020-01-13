<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width"/>
    <style type="text/css">
        <?= file_get_contents(LIGHT_DIR_LIGHTPUBLIC . '/css/Table.css') ?>
        .good,
        .yes {
            color: #468847;
        }

        .gray,
        .skipp {
            color: gray;
        }

        .warning {
            color: #cd853f;
        }

        .bad,
        .error,
        .no {
            color: #b94a48;
        }

        .label-active {
            color: green;
        }

        .label-inactive {
            color: gray;
        }

        .small {
            font-size: 11px;
        }

        .note {
            font-weight: normal;
            font-size: 11px;
            color: silver;
        }
        .emailbody body {
            font-size: 14px;
            font-family: Arial, Helvetica, sans-serif;
        }
        .emailbody .nobr {
            white-space: nowrap;
        }
        .emailbody table.table td {
            padding: 5px;
            border-top: 1px solid #eaf2fb;
        }
        .emailbody table.table th {
            font-size: 13px;
            padding: 5px;
            font-weight: normal;
            background-color: #f9f9f9;
        }
        .emailbody .oldmail, .emailbody .oldmail th, .emailbody .oldmail td, .emailbody .oldmail a {
            font-size: 12px !important;
            color: gray !important;
        }

        .emailbody .oldmail small a {
            font-size: 10px !important;
        }

        .emailbody table.form {
            border-collapse: separate;
            border-spacing: 1px;
            border: 0;
            margin: 0;
            padding: 0;
        }

        .emailbody table.form th {
            text-align: right;
            font-weight: normal;
            padding: 5px 10px 5px 0;
            width: 120px;
        }

        .emailbody table.form td {
            padding: 5px;
            width: 304px;
            background-color: #eaf2fb;
        }

    </style>
</head>
<body class="emailbody">

<?= $body ?>

</body>
</html>
