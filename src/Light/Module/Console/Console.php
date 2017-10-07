<?php
namespace Light\Module\Console;

use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\File;
use Light\Js;
use Light\Request;
use Light\Response;
use Light\Table;
use Light\Template;
use Light\Timer;
use Light\User;

class Console
{
    public function __invoke()
    {
        Js::addLight('Console.js');

        Response::setPageTitle('Console');
        Response::setPageHeadHtml('Console');

        $tdata = [];

        $hgProjectResult = User::getParam('ConsoleHgProjectResult');
        if ($hgProjectResult) {
            $tdata['hgProjectResult'] = $hgProjectResult;
            User::setParam('ConsoleHgProjectResult', '');
        }

        $hgLightResult = User::getParam('ConsoleHgLightResult');
        if ($hgLightResult) {
            $tdata['hgLightResult'] = $hgLightResult;
            User::setParam('ConsoleHgLightResult', '');
        }

        $tdata['sql'] = User::getParam('ConsoleSql');
        $tdata['shellcmd'] = User::getParam('ConsoleShellCmd');

        if (Config::$hgPath) {
            $tdata['projectHgParent'] = self::executeHg(LIGHT_DIR_ROOT, 'parent');
            $tdata['lightHgParent'] = self::executeHg(LIGHT_DIR_LIGHT, 'parent');
        }

        if (Config::$backupFolder) {
            $tdata['backupsHtml'] = self::getBackupTabHtml();
        }

        Template::includeTpl('Console.tpl.php', $tdata);
    }

    public static function proceedDownloadHgBundleProject()
    {
        $bundleFilename = PROJECT_NAMESPACE . '.hg';
        self::sendHgBundle(LIGHT_DIR_ROOT, $bundleFilename);
    }

    public static function proceedDownloadHgBundleLight()
    {
        $bundleFilename = 'Light.hg';
        self::sendHgBundle(LIGHT_DIR_LIGHT, $bundleFilename);
    }

    private static function sendHgBundle($repositoryPath, $bundleFilename)
    {
        $bundleFilepathEnc = File::encode(LIGHT_DIR_TEMP . "/$bundleFilename");
        self::executeHg($repositoryPath, "bundle -a $bundleFilepathEnc");

        Response::header('Content-Description: Dowload HG Bundle');
        Response::header('Content-Type: application/octet-stream');
        Response::header('Content-disposition: attachment; filename=' . $bundleFilename);
        Response::header('Content-Length: ' . filesize($bundleFilepathEnc));
        ob_clean();
        flush();
        readfile($bundleFilepathEnc);
        unlink($bundleFilepathEnc);

        ApplicationAbstract::terminate();
    }

    public static function proceedUploadHgBundleProject()
    {
        if (isset($_FILES['projectbundle'])) {
            $bundleFilepathEnc = File::encode(LIGHT_DIR_TEMP . '/projectbundle.hg');
            move_uploaded_file($_FILES['projectbundle']['tmp_name'], $bundleFilepathEnc);
            $out = self::executeHg(LIGHT_DIR_ROOT, 'pull -u "' . $bundleFilepathEnc . '"', true);
            unlink($bundleFilepathEnc);

        } else {

            $out = '<span class="bad">no file provided</span>';

        }

        User::setParam('ConsoleHgProjectResult', $out);
        Response::redirect('Console');
    }

    public static function proceedUploadHgBundleLight()
    {
        if (isset($_FILES['lightbundle'])) {
            $bundleFilepathEnc = File::encode(LIGHT_DIR_TEMP . '/lightbundle.hg');
            move_uploaded_file($_FILES['lightbundle']['tmp_name'], $bundleFilepathEnc);
            $out = self::executeHg(LIGHT_DIR_LIGHT, 'pull -u "' . $bundleFilepathEnc . '"', true);
            unlink($bundleFilepathEnc);

        } else {

            $out = '<span class="bad">no file provided</span>';

        }

        User::setParam('ConsoleHgLightResult', $out);
        Response::redirect('Console');
    }

    private static function executeHg($repositoryPathEnc, $hgCommand, $showCmd = false)
    {
        $cmd = '"' . Config::$hgPath . '" --repository "' . $repositoryPathEnc . '"';
        $cmd .= ' ' . $hgCommand;

        //Перенаправляем stderr в stdout, чтобы увидеть ошибки, если они есть
        //Для вывода stderr можно было бы использовать proc_open вместо exec, но есть баг: https://bugs.php.net/bug.php?id=60181
        $cmd .= ' 2>&1';

        exec($cmd, $out);

        $outTxt = '';
        if ($showCmd) {
            $outTxt = $cmd . "\n";
        }
        $outTxt .= implode("\n", $out);
        $outTxt = trim($outTxt);

        return $outTxt;
    }

    public static function proceedSql()
    {
        if (!Request::isPost()) {
            Response::abort(403);
        }
        $sql = $_POST['value'];
        User::setParam('ConsoleSql', $sql);
        $timer = new Timer();

        $sqlArr = explode(';', $sql);
        $sqlArr = array_map('trim', $sqlArr);
        $sqlArr = array_filter($sqlArr);

        foreach ($sqlArr as $sql) {

            print '<strong>' . $sql . '</strong>';

            $res = Db::$mysqli->query($sql);
            if ($res === false) {

                //Завершим работу скрипта с информацией об ошибке на экран пользователя
                print '<br><span class="bad">' . Db::$mysqli->error . '</span>';

            } else {

                print '<br>Done in ' . $timer->get() . ' ms';
                print '<br>Affected rows: ' . Db::getAffectedRows();

                if ($res instanceof \mysqli_result && $res->num_rows) {
                    //Если запрос возвращает какие-то строки, попробуем вывести их в аккуратную таблицу
                    $table = new Table();
                    $data = [];
                    $firstRow = true;
                    while ($row = $res->fetch_assoc()) {
                        if ($firstRow) {
                            //Если это первая стрка, то на её основе добавим поля в таблицу
                            foreach ($row as $fieldKey => $_) {
                                $table->getFieldset()->addField(F::create($fieldKey, F::TYPE_STRING));
                            }
                            $firstRow = false;
                        }

                        $data[] = $row;
                    }

                    $table->setTableData($data);
                    print $table->getHtmlTable(false);
                }


            }

            print '<br>';
            print '<br>';

        }

        ApplicationAbstract::terminate();
    }

    public static function proceedShell()
    {
        if (!Request::isPost()) {
            Response::abort(403);
        }
        $cmd = $_POST['value'];
        User::setParam('ConsoleShellCmd', $cmd);

        //Перенаправляем stderr в stdout, чтобы увидеть ошибки, если они есть
        //Для вывода stderr можно было бы использовать proc_open вместо exec, но есть баг: https://bugs.php.net/bug.php?id=60181
        $cmd .= ' 2>&1';

        exec($cmd, $out);
        $outTxt = implode("\n", $out);
        $outTxt = trim($outTxt);

        if (PHP_OS == 'WINNT') {
            $outTxt = mb_convert_encoding($outTxt, 'UTF8', 'cp866');
        }

        print htmlspecialchars($outTxt);
        ApplicationAbstract::terminate();
    }

    /**
     * Метод вызывается ajax запросом при активации таба backup
     */
    private static function getBackupTabHtml()
    {
        if (!is_dir(Config::$backupFolder)) {
            return '<p class="bad">Folder ' . htmlspecialchars(Config::$backupFolder) . ' not found</p>';
        }

        $files = [];
        foreach (scandir(Config::$backupFolder) as $fileEnc) {
            if ($fileEnc[0] === '.') {
                continue;
            }
            $filepathEnc = Config::$backupFolder . DIRECTORY_SEPARATOR . $fileEnc;
            $time = filemtime($filepathEnc);

            $file = File::decode($fileEnc);
            $files[$time] = '<li><a href="?a=Console&ps=getBackupFile&file=' . rawurlencode($file) . '">'
                . htmlspecialchars($file)
                . ' <small>(' . round(filesize($filepathEnc) / 1024) . ' Kb)</small>'
                . '</a></li>';
        }

        ksort($files);

        //Выводим первые 5

        return '<ol>' . implode('<br>', array_slice($files, 0, 5)) . '</ol>';
    }

    public static function proceedGetBackupFile()
    {
        //На всякий случай убираем все пути из пришедшего GET параметра, чтобы осталсоь чистое имя файла, как и задумывалось
        $filename = $_GET['file'];
        $filenameEnc = File::encode($filename);

        $filepathEnc = Config::$backupFolder . DIRECTORY_SEPARATOR . $filenameEnc;
        if (!file_exists($filepathEnc)) {
            Response::abort(404, 'File ' . $filepathEnc . ' not found');
        }

        Response::header('Content-Description: Dowload SQL Backup');
        Response::header('Content-Type: application/octet-stream');
        Response::header('Content-disposition: attachment; filename=' . $filename);
        Response::header('Content-Length: ' . filesize($filepathEnc));
        ob_clean();
        flush();
        readfile($filepathEnc);

        ApplicationAbstract::terminate();
    }

    public static function proceedDownloadProductionBackup()
    {
        $datetime = date('Y-m-d_H_i_s');

        $filename = Config::$mysqlDb . '_production_' . $datetime . '.sql';
        $filepathEnc = File::encode(LIGHT_DIR_TEMP . "/$filename");

        $cmdBase = 'mysqldump';
        $cmdBase .= ' -h' . Config::$mysqlHost;
        if (Config::$mysqlPort) {
            $cmdBase .= ' -P' . Config::$mysqlPort;
        }
        $cmdBase .= ' -u' . Config::$mysqlUser;
        $cmdBase .= ' -p' . Config::$mysqlPassword;

        //Сохраним все данные, кроме dblog
        $cmd = $cmdBase;
        $cmd .= ' --ignore-table=' . Config::$mysqlDb . '.dblog';
        $cmd .= ' --ignore-table=' . Config::$mysqlDb . '.dblog_field';
        $cmd .= ' --ignore-table=' . Config::$mysqlDb . '.dblog_module';
        $cmd .= ' --ignore-table=' . Config::$mysqlDb . '.dblog_table';
        $cmd .= ' --ignore-table=' . Config::$mysqlDb . '.dblog_text';

        //Перенаправляем stderr в stdout, чтобы увидеть ошибки, если они есть
        //Для вывода stderr можно было бы использовать proc_open вместо exec, но есть баг: https://bugs.php.net/bug.php?id=60181
        $cmd .= ' 2>&1';
        $cmd .= ' ' . Config::$mysqlDb . ' > ' . $filepathEnc;

        exec($cmd, $out, $result);
        if ($result !== 0) {
            $outTxt = implode('<br>', $out);
            if (PHP_OS == 'WINNT') {
                $outTxt = mb_convert_encoding($outTxt, 'UTF8', 'cp866');
            }

            //Выведем сообщение об ошибке. При этом на экране уже не будет обвязки связанной с модулем Console
            //Но зато будет меню слева, т.к. мы не делаем ApplicationAbstract::terminate()
            print '<p class="bad">' . $outTxt . '</p>';
            return;
        }

        //Сохраним структуру dblog
        $filenameDblog = Config::$mysqlDb . '_production_' . $datetime . '_dblog_structure.sql';
        $filepathDblogEnc = File::encode(LIGHT_DIR_TEMP . "/$filenameDblog");

        $cmd = $cmdBase;
        //Опция -d не выводит данные, только структуру
        $cmd .= ' -d';
        $cmd .= ' ' . Config::$mysqlDb;
        $cmd .= ' dblog';
        $cmd .= ' dblog_field';
        $cmd .= ' dblog_module';
        $cmd .= ' dblog_table';
        $cmd .= ' dblog_text';
        $cmd .= ' > ' . $filepathDblogEnc;

        exec($cmd, $out);

        //ЗИП! ФАЙЛ!
        $zip = new \ZipArchive();
        $zipFilepathEnc = $filepathEnc . '.zip';

        $zip->open($zipFilepathEnc, \ZipArchive::CREATE);
        $zip->addFile($filepathEnc, mb_convert_encoding($filename, 'CP866', 'UTF-8'));
        $zip->addFile($filepathDblogEnc, mb_convert_encoding($filenameDblog, 'CP866', 'UTF-8'));
        $zip->close();
        unlink($filepathEnc);

        Response::header('Content-Description: Dowload Production SQL Backup');
        Response::header('Content-Type: application/octet-stream');
        Response::header('Content-disposition: attachment; filename=' . $filename . '.zip');
        Response::header('Content-Length: ' . filesize($zipFilepathEnc));
        ob_clean();
        flush();
        readfile($zipFilepathEnc);
        unlink($zipFilepathEnc);

        ApplicationAbstract::terminate();

    }

    public static function proceedPhpInfo()
    {
        phpinfo();
    }

}
