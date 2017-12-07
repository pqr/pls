<?php

namespace Light;

use Light\Field\FieldAbstract as F;
use Light\Traits\FieldsetAwareTrait;

class PrintForm
{
    use FieldsetAwareTrait;

    const FORMAT_HTML = 'html';
    const FORMAT_PDF = 'pdf';
    const FORMAT_DOC = 'doc';
    const FORMAT_EXCEL = 'xlsx';

    const OUT_SAVE = 0b01;
    const OUT_STREAM = 0b10;
    const OUT_SAVE_AND_STREAM = 0b11;

    protected $key; // условное название этой печатной формы (выводится на кнопке и в выпадающем списке)
    protected $title; // начало текста, который попадёт в начало имени файла и в заголовок html страницы (при выводе в браузер)
    protected $format; // в каком формате в итоге генерировать документ
    protected $out; // что сделать с резульатом? Сохранить в папку объекта или отправить в браузер пользователю или и то и другое?

    /** @var Fieldset|F[] */
    protected $_fieldset;

    public function __construct(string $key, string $title, $format = self::FORMAT_HTML, int $out = self::OUT_STREAM)
    {
        $this->key = $key;
        $this->title = $title;
        $this->format = $format;
        $this->out = $out;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getOut()
    {
        return $this->out;
    }

    public function setOut(string $out)
    {
        $this->out = $out;
    }

    final public function getFieldset()
    {
        // Лениво инициализируем fieldset, если его ещё нет
        // обычно его нет на момент запуска конструктора, когда дочерние классы делают $this->addFields(...)
        if (!$this->_fieldset) {
            $this->_fieldset = new Fieldset();
        }

        return $this->_fieldset;
    }

    /**
     * Метод для переопределения в классах потомках - проверяет, подходит ли данная печатная форма для данного объекта?
     * Этот метод вызывается при формировании меню из кнопок на печать ModuleAbstract::getSubmenuItemsTplData
     * @param ObjAbstract $obj
     * @return bool
     */
    public function testForObj(ObjAbstract $obj)
    {
        return true;
    }

    /**
     * Возвращает полный текст, который попадёт в заголовок html страницы
     * @param ObjAbstract $obj
     * @return string
     */
    protected function getHtmlTitle(ObjAbstract $obj)
    {
        return $this->title . ' ' . strip_tags($obj->getDescriptionHtml());
    }

    /**
     * Возвращает полный текст, который попадёт в заголовок html страницы, если на печать выведено одновременно несколько объектов
     * @param int $count
     * @return string
     */
    protected function getHtmlTitleMultiple(int $count)
    {
        return $this->title . ' (' . $count . ')';
    }

    /**
     * Возвращает имя файла шаблона, которое потом будет передано в Template::parseTpl
     * Если вернуть относительный путь, то Template::parseTpl будет искать в папке текущего модуля
     * Можно вернуть абсолютный путь для надёжности
     *
     * По умолчанию в базовом классе PrintForm подразумеваем, что шаблоны должны лежать в папке PrintForms внутри папки
     * текущего модуля
     *
     * @param ObjAbstract $obj
     * @return string
     * @throws \Exception
     */
    protected function getTemplate(ObjAbstract $obj = null)
    {
        if ($this->format == self::FORMAT_EXCEL) {
            throw new \Exception('При печати в Excel, метод getTemplate должен быль переопределен');
        }
        return 'PrintForms/' . File::getValidFilename($this->key, true) . '.tpl.php';
    }

    /**
     * В случае если файл сохраняется в папку объекта, или отправляется в браузер пользотелю как PDF или DOC
     * нужно сформировать имя этого файла
     * @param ObjAbstract $obj
     * @return string
     */
    public function getFilename(ObjAbstract $obj)
    {
        $filename = $this->getHtmlTitle($obj) . '.' . $this->format;
        return File::getValidFilename($filename);
    }

    protected function getFilenameMultiple(int $count)
    {
        $filename = $this->getHtmlTitleMultiple($count) . '.' . $this->format;
        return File::getValidFilename($filename);
    }

    /**
     * Для переопределения в классах потомках, если нужно добавить какие-то дополнительные данные для вывода на печать
     * в зависимости от полученного объекта
     * @param ObjAbstract $obj
     * @return array
     */
    protected function getTplData(ObjAbstract $obj)
    {
        $tdata = $obj->getTplData(F::MODE_VIEW);
        $tdata['_printForm'] = $this->key;
        return $tdata;
    }

    protected function getDialogDataFromRequest(): array
    {
        if ($this->getFieldset()->count()) {
            $dialogData = $this->getFieldset()->getDataFromRequest($_POST);
            if ($dialogData) {
                return $dialogData;
            }
        }
        return [];
    }

    /**
     * Возвращает HTML код, который потом будет помещён в body тег в мастер шаблон
     * Если происходит печать нескольких документов, то этот метод будет вызван много раз и все полученные html блоки
     * будут склеены и помещены в body тег мастер шаблона
     *
     * @param ObjAbstract $obj
     * @return string
     */
    public function getBodyHtml(ObjAbstract $obj)
    {
        $tdata = array_merge($this->getTplData($obj), $this->getDialogDataFromRequest());
        $template = $this->getTemplate($obj);
        return Template::parseTpl($template, $tdata);
    }

    protected function getFullHtml(ObjAbstract $obj)
    {
        $title = $this->getHtmlTitle($obj);
        $bodyHtml = $this->getBodyHtml($obj);
        return Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/module/PrintFormsMaster.tpl.php',
            ['title' => $title, 'bodyHtml' => $bodyHtml]);
    }

    public function getDataForAjaxDialog(ObjAbstract $obj): array
    {
        return [];
    }

    public function getDataForAjaxDialogMultiple(array $ids, array $objectsAndPaths): array
    {
        return [];
    }

    protected function getDataForExcel(ObjAbstract $obj)
    {
        return array_merge($obj->getExcelData(), $this->getDialogDataFromRequest());
    }

    protected function writeToActiveSheet(XlsWriter $xls, ObjAbstract $obj, $title = null)
    {
        if ($title) {
            $sheet = $xls->getPhpExcelObj()->getActiveSheet();
            $sheet->setTitle($title);
        }
        $dataForExcel = $this->getDataForExcel($obj);
        $fieldset = new Fieldset();
        $fieldset->addFields($obj->getFieldset());
        $fieldset->addFields($this->getFieldset());
        $this->replaceExcelData($xls, $dataForExcel, $fieldset);
    }

    protected function replaceExcelData(XlsWriter $xls, array $dataForExcel, Fieldset $fieldset)
    {
        $xls->replaceData($dataForExcel, $fieldset);
    }

    public function proceedPrint(ObjAbstract $obj, string $filesFolderRelativePath = '')
    {
        $timer = new Timer();

        $fullHtml = '';
        if ($this->format != self::FORMAT_EXCEL) {
            $fullHtml = $this->getFullHtml($obj);
        }

        // Подготовим пару вспомагательных переменных (они не во всех случаях понадобятся, но проще их заготовить сразу)
        $filename = $this->getFilename($obj);

        if ($this->out & self::OUT_SAVE) {
            if ($filesFolderRelativePath === '') {
                throw new \InvalidArgumentException('$filesFolderRelativePath not provided to PrintFrom::proceedPrint()');
            }
            // На всякий случай создадим эту папку (возможно её вообще нет?)
            FilesFolder::mkdir($filesFolderRelativePath);
            // Заранее сформируем полный путь к файлу в папке объекта
            $destFilepathAbsEnc = File::encode(FilesFolder::getAbsolutePath($filesFolderRelativePath . '/' . $filename));
        } else {
            $destFilepathAbsEnc = '';
        }

        // Дальше разный подход в зависимости от формата
        switch ($this->format) {

            case self::FORMAT_HTML:
                if ($this->out & self::OUT_STREAM) {
                    // Отправляем в браузер пользователю полный HTML
                    print $fullHtml;
                }
                if ($this->out & self::OUT_SAVE) {
                    // Сохраняем в папку объекта
                    @file_put_contents($destFilepathAbsEnc, Misc::makeLinksAbsolute($fullHtml));
                    @chmod($destFilepathAbsEnc, 0666);
                }
                break;


            case self::FORMAT_PDF:
                // В случае PDF формата в любом случае придётся создать временный PDF файл
                $tempFilepathAbs = tempnam(LIGHT_DIR_TEMP, 'print');
                Pdf::save($fullHtml, $tempFilepathAbs);
                if ($this->out & self::OUT_STREAM) {
                    // Отправляем в браузер пользователю pdf файл из временной папки
                    Response::streamFileFromContent(file_get_contents($tempFilepathAbs), $filename);
                }
                if ($this->out & self::OUT_SAVE) {
                    // Перемещаем временный файл в папку объекта
                    @rename($tempFilepathAbs, $destFilepathAbsEnc);
                    @chmod($destFilepathAbsEnc, 0666);
                } else {
                    // Если перемещения не было, просто удалим временный файл
                    unlink($tempFilepathAbs);
                }
                break;


            case self::FORMAT_DOC:
                $fullHtml = Misc::makeLinksAbsolute($fullHtml);
                // Для Word файлов подход иной: cначала перемещам в папку объекта (если нужно)
                if ($this->out & self::OUT_SAVE) {
                    // Сохраним в папку объекта
                    // этот код аналогичен сохранению html формата в папку объекта
                    // т.к. word файл - это на самом деле тот же самый html, только с расширением doc
                    file_put_contents($destFilepathAbsEnc, $fullHtml);
                    @chmod($destFilepathAbsEnc, 0666);
                }
                // Затем, если требуется отправить в браузер пользователю, то это делается двумя способами:
                // либо непосредсвенно из папки, куда переместили
                // либо динамически выводим в браузер (если в папку не перемещали)
                if ($this->out & self::OUT_STREAM) {

                    // Выводим в браузер пользователю обычный html но с такими заголовками, чтобы у него открылся Word
                    Response::streamFileFromContent($fullHtml, $filename);

                    /*
                     * Код ниже закомментирован, т.к. в новом Firefox при попытке сделать FilesFolder::streamFileTryWindowsShared() получаем сообщение "Ошибка искажения содержимого"
                    if ($this->out & self::OUT_SAVE && FilesFolder::isWindowsSharedFolderAllowed()) {
                        // Если файл doc был сохранён в папку
                        // то сразу запустится word, где можно отредактировать, нажать Ctrl+S и файл будет сохранён в папке - очень удобно!
                        FilesFolder::streamFileTryWindowsShared($filesFolderRelativePath . '/' . $filename);
                    } else {
                        // Выводим в браузер пользователю обычный html но с такими заголовками, чтобы у него открылся Word
                        Response::streamFileFromContent($fullHtml, $filename);
                    }
                    */
                }
                break;

            case self::FORMAT_EXCEL:
                $xls = new XlsWriter(Template::findfile($this->getTemplate($obj)));
                $this->writeToActiveSheet($xls, $obj);
                if ($this->out & self::OUT_SAVE) {
                    $xls->save($destFilepathAbsEnc);
                    @chmod($destFilepathAbsEnc, 0666);
                }
                if ($this->out & self::OUT_STREAM) {
                    $xls->send($filename);
                }
                break;
        }

        Metric::record(Request::getModule() . '::proceedPrint ' . $this->key, $timer->getMs(), 'ID: ' . $obj->getId());

    }

    public function proceedPrintMultiple(array $objectsAndPaths)
    {
        $timer = new Timer();

        // Вспомагательная массив - пригодится, если результат нужно вывести в браузер пользователю как HTML или как Word файл
        $bodyHtmls = [];
        // Вспомагательный массив с именами временных PDF файлов - пригодится, если нужно вывести в браузер пользователю
        // большой склеенный PDF файл
        $tempFilepaths = [];

        if ($this->format === self::FORMAT_EXCEL) {
            $xls = new XlsWriter(Template::findfile($this->getTemplate()));
            $phpExcelObj = $xls->getPhpExcelObj();
            $sheetTemplate = $phpExcelObj->getActiveSheet()->copy();
            $phpExcelObj->removeSheetByIndex(0);
            $sheetIndex = -1;
        }

        /** @var ObjAbstract $obj */
        foreach ($objectsAndPaths as [$obj, $filesFolderRelativePath]) {

            $fullHtml = '';
            if ($this->format !== self::FORMAT_EXCEL) {
                $fullHtml = $this->getFullHtml($obj);
            }

            // Подготовим пару вспомагательных переменных (они не во всех случаях понадобятся, но проще их заготовить сразу)
            $filename = $this->getFilename($obj);

            if ($this->out & self::OUT_SAVE) {
                if ($filesFolderRelativePath === '') {
                    throw new \InvalidArgumentException('$filesFolderRelativePath not provided to PrintFrom::proceedPrintMultiple()');
                }
                // На всякий случай создадим папку в которую будем сохранять результат (возможно её вообще нет?)
                FilesFolder::mkdir($filesFolderRelativePath);
                // Заранее сформируем полный путь к файлу в папке объекта
                $destFilepathAbsEnc = File::encode(FilesFolder::getAbsolutePath($filesFolderRelativePath . '/' . $filename));
            } else {
                $destFilepathAbsEnc = '';
            }

            switch ($this->format) {
                case self::FORMAT_HTML:
                    if ($this->out & self::OUT_STREAM) {
                        // Результат нужно вывести в браузер пользователю, значит накопим html тела в массив
                        $bodyHtmls[] = $this->getBodyHtml($obj);
                    }
                    if ($this->out & self::OUT_SAVE) {
                        // Сохраняем в папку объекта
                        file_put_contents($destFilepathAbsEnc, Misc::makeLinksAbsolute($fullHtml));
                        @chmod($destFilepathAbsEnc, 0666);
                    }
                    break;


                case self::FORMAT_PDF:
                    // В случае PDF формата в любом случае придётся создать временный PDF файл
                    $tempFilepathAbs = tempnam(LIGHT_DIR_TEMP, 'print');
                    Pdf::save($fullHtml, $tempFilepathAbs);
                    if ($this->out & self::OUT_STREAM) {
                        // Пользователю нужно будет отправить резултат в виде общего PDF файла
                        // Для этого сохраним имя временного файла в массиве
                        $tempFilepaths[] = $tempFilepathAbs;
                    }
                    if ($this->out & self::OUT_SAVE) {
                        // Сохраним файл в папке объекта
                        if ($this->out & self::OUT_STREAM) {
                            // Если пользователю нужно отправить результат в браузер, то временный файл не перемещаем, а копируем
                            copy($tempFilepathAbs, $destFilepathAbsEnc);
                        } else {
                            // Если же пользователю не нужно отправлять результат в браузер, то временный файл можно переместить в папку объекта
                            rename($tempFilepathAbs, $destFilepathAbsEnc);
                        }
                        @chmod($destFilepathAbsEnc, 0666);
                    }
                    break;

                case self::FORMAT_DOC:
                    if ($this->out & self::OUT_STREAM) {
                        // Результат нужно вывести в браузер пользователю, значит накопим html тела в массив
                        $bodyHtmls[] = $this->getBodyHtml($obj);
                    }
                    if ($this->out & self::OUT_SAVE) {
                        // Сохраним в папку объекта
                        // этот код аналогичен сохранению html формата в папку объекта
                        // т.к. word файл - это на самом деле тот же самый html, только с расширением doc
                        file_put_contents($destFilepathAbsEnc, Misc::makeLinksAbsolute($fullHtml));
                        @chmod($destFilepathAbsEnc, 0666);
                    }
                    break;

                case self::FORMAT_EXCEL:
                    $sheet = $sheetTemplate->copy();
                    $sheetIndex++;
                    $phpExcelObj->addSheet($sheet, $sheetIndex);
                    $phpExcelObj->setActiveSheetIndex($sheetIndex);
                    $title = $obj->getFieldText($obj->getNameFieldKey());
                    $this->writeToActiveSheet($xls, $obj, $title);
                    if ($this->out & self::OUT_SAVE) {
                        $xls->save($destFilepathAbsEnc);
                        @chmod($destFilepathAbsEnc, 0666);
                    }
                    break;
            }

        }

        // В предыдущем цикле мы сохранили все документы по папкам объектов (если того требует текущая форма)
        // и накопили какие-то данные в $outBodyHtmls или $tempFilepaths
        // Если нужно вывести в браузер пользователю, то самое время воспользоваться этими накопительными массивами!
        if ($this->out & self::OUT_STREAM) {

            $count = count($objectsAndPaths);
            $filenameMultiple = $this->getFilenameMultiple($count);
            $titleMultiple = $this->getHtmlTitleMultiple($count);

            switch ($this->format) {
                case self::FORMAT_HTML:

                    $pageBreak = '<div style="page-break-after: always"></div>';
                    $bodyHtml = implode($pageBreak, $bodyHtmls);
                    print Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/module/PrintFormsMaster.tpl.php',
                        ['title' => $titleMultiple, 'bodyHtml' => $bodyHtml]);
                    break;

                case self::FORMAT_PDF:
                    // В случае PDF формата склеем все временные файлы в один и отдадим пользователю в браузер
                    Pdf::concatenatePdfsAndStream($tempFilepaths, $filenameMultiple);
                    // Терерь нужно удалить временные файлы
                    foreach ($tempFilepaths as $tempFilepath) {
                        unlink($tempFilepath);
                    }
                    break;

                case self::FORMAT_DOC:
                    $pageBreak = '<br clear=all style="mso-special-character:line-break;page-break-before:always">';
                    $bodyHtml = implode($pageBreak, $bodyHtmls);
                    $fullHtml = Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/module/PrintFormsMaster.tpl.php',
                        ['title' => $titleMultiple, 'bodyHtml' => $bodyHtml]);
                    // Выводим в браузер пользователю обычный html но с такими заголовками, чтобы у него открылся Word
                    Response::streamFileFromContent(Misc::makeLinksAbsolute($fullHtml), $filenameMultiple);
                    break;

                case self::FORMAT_EXCEL:
                    $xls->send($filename);
                    break;
            }
        }

        Metric::record(Request::getModule() . '::proceedPrintMultiple ' . $this->key, $timer->getMs(),
            'Count: ' . count($objectsAndPaths));
    }

    public function isLinkBlank()
    {
        return ($this->out & self::OUT_STREAM) && $this->format === self::FORMAT_HTML;
    }

}
