<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?= $title ?></title>
    <style type="text/css">
        html, body {
            margin: 0;
            padding: 2px;
            font-family: Verdana, Arial, sans-serif;
            font-size: 13px;
            background-color: #FFFFFF;
        }

        @media screen {
            body {
                width: 210mm;
            }
        }

        .table {
            border-collapse: collapse;
            width: 100%;
        }

        .table-headblock {
            display: none;
        }

        .table td, .table th {
            padding: 2px;
            border: 1px solid #000;
        }

        .table tr{
            page-break-inside: avoid;
        }

        table.table tr.total {
            font-weight: bold;
        }

        table.table td.tar {
            text-align: right;
        }

        table.table td.tac {
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }

        .avoid-break {
            page-break-inside: avoid;
        }

        .nobr {
            white-space: nowrap;
        }

        div.text-ajax-container, div.text-ajax-container > div {
            display: inline;
        }

        a.text-ajax {
            display: none;
        }
    </style>
</head>
<body>
<?= \Light\PrintForm\ConcatinaterHtml::PLACEHOLDER_BEGIN ?>
<?= $bodyHtml ?>
<?= \Light\PrintForm\ConcatinaterHtml::PLACEHOLDER_END ?>
</body>
</html>