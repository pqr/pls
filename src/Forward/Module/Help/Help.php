<?php

use Light\Template;
use Light\ApplicationAbstract;
use Light\FilesFolder;
use Light\Config;
use Light\Response;

// Чтобы правильно работала функция basename с файлами на русском языке
// Перед этим нужно установить локаль в операционную систему:
// https://askubuntu.com/questions/76013/how-do-i-add-locale-to-ubuntu-server
// locale -a
// sudo locale-gen ru_RU
// sudo locale-gen ru_RU.UTF-8
// sudo update-locale
// sudo service php7.1-fpm restart
if (isset($_GET['elfinder'])) {

    if ($_GET['elfinder'] === 'dialog') {
        // Это первичный запрос на рендер каркаса диалога (пока без содержимого с файлами)
        Template::includeTpl(LIGHT_DIR_LIGHT . '/templates/module/elfinderdialog.tpl.php', [
            'module' => 'Help',
            'id' => 0,
            'title' => 'Help'
        ]);
        ApplicationAbstract::terminate();
        return;
    }

    if ($_GET['elfinder'] === 'api') {
        // Пришел API запрос от окна с elfiner (это может быть API запрос на отображение списка файлов
        // или upload нового файла
        $opts = [
            'locale' => "ru_RU.utf8",
            'roots' => [
                [
                    'driver' => 'LocalFileSystem',
                    'encoding' => 'UTF-8',
                    'locale' => "ru_RU.utf8",
                    'mimeDetect' => 'internal',
                    // если не установить internal, то будет использована функция finfo_file, которая при обнаружении xls файла сбрасывает локаль в default заничеие ('C') и дальше все файлы с русскими буквами не смогут быть обработаны с помощью basename
                    // driver for accessing file system (REQUIRED)
                    'path' => Config::$filesfolderLocal . '/Help',
                    'URL' => "?a=Help&elfinder=download&file=",
                    // URL to files (REQUIRED)
                    'accessControl' => function ($attr, $path, $data, $volume) {
                        if (strpos(basename($path), '.') === 0) {
                            // Для путей начинающихся с .., если запрошен атрибут read или write - сразу вернём false
                            if ($attr === 'read' || $attr === 'write') {
                                return false;
                            }
                        }

                        // Если elfinder хочет проверить атрибут на запись, делегируем эту проверку в ModuleAbstract
                        /*
                        if ($attr === 'write') {
                            return $this->hasWriteAccessRightToFolder($this->_obj);
                        }
                        */

                        // Возвращаем null - elfinder должен решить сам, что делать
                        return null;

                    }
                ]
            ],
            'bind' => [
                'mkdir mkfile rename duplicate upload rm paste' => function ($cmd, $result, $args, $elfinder) {

                    /* @var \elFinder $elfinder */

                    if (!empty($result['added'])) {
                        foreach ($result['added'] as $file) {
                            $mode = isset($file['mime']) && $file['mime'] === 'directory' ? 0777 : 0666;

                            @chmod($elfinder->realpath($file['hash']), $mode);
                        }
                    }

                    if (!empty($result['changed'])) {
                        foreach ($result['changed'] as $file) {
                            $mode = isset($file['mime']) && $file['mime'] === 'directory' ? 0777 : 0666;
                            @chmod($elfinder->realpath($file['hash']), $mode);
                        }
                    }
                },
            ],
        ];

        // run elFinder
        $connector = new \elFinderConnector(new \elFinder($opts));
        $connector->run();
        ApplicationAbstract::terminate();
        return;
    }

    if ($_GET['elfinder'] === 'download' && isset($_GET['file'])) {

        // Проверим, чтобы в имени скачиваемого файла file не было ..
        $test = '/' . str_replace('\\', '/', $_GET['file']) . '/';
        if (strpos($test, '/../') !== false) {
            throw new \InvalidArgumentException("file contains ..: " . $_GET['file']);
        }

        $filepathAbs = Config::$filesfolderLocal . '/Help/' . $_GET['file'];
        Response::streamFileFromFilesystem($filepathAbs);
        ApplicationAbstract::terminate();
        return;
    }
}


\Light\Response::setPageHeadHtml('Help');
\Light\Response::setPageTitle('Help');
?>

<h2>Полезная информация</h2>
<ul>
    <li><?= \Light\FilesFolder::getLink('Help/0', 'Открыть папку с файлами с полезной информацией') ?></li>
</ul>

<?php
$pdfs = glob(LIGHT_DIR_PUBLIC . "/Module/Help/*.pdf");
if ($pdfs): ?>

    <h2>Инструкции в PDF</h2>
<ul>
    <?php
    foreach ($pdfs as $pdf):
        //$pdf = \Light\File::decode($pdf);
        $pdf = basename($pdf);
        ?>
        <li><a href="Module/Help/<?= htmlspecialchars(rawurlencode($pdf)) ?>" target="_blank"><?= $pdf ?></a></li>
    <?php
    endforeach;
    endif;
    ?>
</ul>

<h2>Цвета</h2>
<ul style="list-style: none;">
    <?php
    $colorHints = \Light\Field\FieldColor::getColorHints();
    for ($i = 1; $i <= 11; $i++):
        ?>
        <li style="margin-bottom: 3px;"><span class="color-span color<?= $i ?>"><?= $i ?></span>
            <?= $colorHints['ColorHint' . $i] ?>
        </li>
    <?php
    endfor;
    ?>
</ul>


<?php
$token = \Light\Db::getOne("SELECT TelegramToken FROM user WHERE ID = @i", \Light\User::getId());
if ($token): ?>
    <br><br>
    Вам подключён Telegram бот!
    <p>
        Откройте в Telegram бота с именем <strong><a href="https://telegram.me/ProactiveLogisticBot" target="_blank">@ProactiveLogisticBot</a></strong>
        и выполните команду
        <strong>/login <?= $token ?></strong>
    </p>
    <p>
        <?= nl2br(file_get_contents(__DIR__ . '/../Bot/BotStartMessageAuthorized.html')); ?>
    </p>

<?php endif; ?>
