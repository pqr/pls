<?php


namespace Light;


class ElfinderPage
{
    public static function renderPage(string $title, string $apiUrl): void
    {
        Response::setMasterTemplate(null);
        Template::includeTpl('ElfinderPageMaster.tpl.php', [
            'title' => $title,
            'apiUrl' => $apiUrl,
        ]);
    }

    public static function proceedApi(string $path, string $downloadUrl, bool $hasWriteAccess = true): void
    {
        Response::setMasterTemplate(null);

        $opts = [
            'locale' => "ru_RU.utf8",
            'roots' => [
                [
                    'driver' => 'LocalFileSystem',
                    'encoding' => 'UTF-8',
                    'locale' => "ru_RU.utf8",
                    'mimeDetect' => 'internal',
                    // если не установить internal, то будет использована функция finfo_file,
                    // которая при обнаружении xls файла сбрасывает локаль в default заничеие ('C')
                    // и дальше все файлы с русскими буквами не смогут быть обработаны с помощью basename
                    // driver for accessing file system (REQUIRED)
                    'path' => $path,
                    'URL' => $downloadUrl,
                    'quarantine' => LIGHT_DIR_TEMP . '/elfinder_quarantine',
                    'tmbPath' => LIGHT_DIR_TEMP . '/elfinder_thumbnails',
                    // URL to files (REQUIRED)
                    'accessControl' => static function ($attr, $path, $data, $volume) use ($hasWriteAccess) {
                        if (str_starts_with(basename($path), '.')) {
                            // Для путей начинающихся с .., если запрошен атрибут read или write - сразу вернём false
                            if ($attr === 'read' || $attr === 'write') {
                                return false;
                            }
                        }

                        // Если elfinder хочет проверить атрибут на запись, делегируем эту проверку в ModuleAbstract
                        if ($attr === 'write') {
                            return $hasWriteAccess;
                        }

                        // Возвращаем null - elfinder должен решить сам, что делать
                        return null;

                    },
                ],
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
    }
}
