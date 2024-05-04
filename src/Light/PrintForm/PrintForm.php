<?php

namespace Light\PrintForm;

use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\File;
use Light\Ico;
use Light\Metric;
use Light\Misc;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;
use Light\Template;
use Light\Timer;
use Light\Traits\FieldsetAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class PrintForm
{
    use FieldsetAwareTrait;

    public const FORMAT_HTML = 'html';
    public const FORMAT_PDF = 'pdf';
    public const FORMAT_DOC = 'doc';
    public const FORMAT_DOCX = 'docx';
    public const FORMAT_EXCEL = 'xlsx';

    public const TEMPLATE_FORMAT_PHP = 'php';
    public const TEMPLATE_FORMAT_EXCEL = 'xlsx';
    public const TEMPLATE_FORMAT_DOCX = 'docx';

    public const OUT_SAVE = 0b01;
    public const OUT_STREAM = 0b10;
    public const OUT_SAVE_AND_STREAM = 0b11;

    protected $key; // условное название этой печатной формы (выводится на кнопке и в выпадающем списке)
    protected $title; // в каком формате в итоге генерировать документ
    protected $templateFormat;
    protected $out; // что сделать с резульатом? Сохранить в папку объекта или отправить в браузер пользователю или и то и другое?
    protected $pdfLandscape = false;
    protected $excelTableOpts = 0; // опции для вывода таблиц в шаблон (см XlsWriter::replaceData())

    /** @var Fieldset|F[] */
    protected $fieldset;

    public $allowSingle = true;
    public $allowMultiple = true;
    public $allowMultipleAsOne = false;

    public function __construct(string $key, string $title, protected $format = self::FORMAT_HTML, int $out = self::OUT_STREAM)
    {
        $this->key = $key;
        $this->title = $title;
        $this->out = $out;

        // На основе выходного формата определим формат шаблона по умолчанию (можно переопределить в конструкторе потомка)
        $this->templateFormat = match ($this->format) {
            self::FORMAT_EXCEL => self::TEMPLATE_FORMAT_EXCEL,
            self::FORMAT_DOCX => self::TEMPLATE_FORMAT_DOCX,
            default => self::TEMPLATE_FORMAT_PHP,
        };

        if ($this->format === self::FORMAT_DOCX) {
            $this->allowMultiple = false;
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getOut(): int
    {
        return $this->out;
    }

    public function setOut(int $out): void
    {
        $this->out = $out;
    }

    public function setPdfLandscape(bool $isLandscape = true): void
    {
        $this->pdfLandscape = $isLandscape;
    }

    final public function getFieldset(): Fieldset
    {
        // Лениво инициализируем fieldset, если его ещё нет
        // обычно его нет на момент запуска конструктора, когда дочерние классы делают $this->addFields(...)
        if (!$this->fieldset) {
            $this->fieldset = new Fieldset();
        }

        return $this->fieldset;
    }

    /**
     * Метод для переопределения в классах потомках - проверяет, подходит ли данная печатная форма для данного объекта?
     * Этот метод вызывается при формировании меню из кнопок на печать ModuleAbstract::getSubmenuItemsTplData
     * @return bool
     */
    public function testForObj(ObjAbstract $obj): bool
    {
        return true;
    }

    /**
     * Метод для переопределения в классах потомках - проверяет, подходит ли данная печатная форма для нескольких
     * объектов (massaction)
     * @param ObjAbstract $obj
     * @return bool
     */
    public function testMultiple(array $ids): ?string
    {
        return null;
    }

    /**
     * Возвращает текст, который попадёт в заголовок html страницы
     * @return string
     */
    protected function getTitleHtml(ObjAbstract $obj): string
    {
        return $this->title . ' ' . strip_tags((string) $obj->getDescriptionHtml());
    }

    /**
     * Возвращает текст, который попадёт в название Excel листа
     * @return string
     */
    protected function getTitleExcelSheet(ObjAbstract $obj): string
    {
        return strip_tags((string) $obj->getDescriptionHtml());
    }

    /**
     * Возвращает текст, который попадёт в заголовок html страницы, если на печать выведено одновременно несколько объектов
     * @return string
     */
    protected function getTitleHtmlMultiple(int $count): string
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
     * @return string
     */
    protected function getTemplate(ObjAbstract $obj): string
    {
        $ext = match ($this->templateFormat) {
            self::TEMPLATE_FORMAT_EXCEL => 'xlsx',
            self::TEMPLATE_FORMAT_DOCX => 'docx',
            default => 'tpl.php',
        };

        $validFilename = File::getValidFilename($this->key, true);
        $module = Misc::getModule($obj);
        return LIGHT_DIR_PROJECT . "/Module/$module/PrintForms/$validFilename.$ext";
    }

    /**
     * В случае если файл сохраняется в папку объекта, или отправляется в браузер пользотелю как PDF или DOC
     * нужно сформировать имя этого файла
     *
     * Делаем public - иногда нужно доступаться из вне
     *
     * @return string
     */
    public function getFilename(ObjAbstract $obj): string
    {
        $filename = $this->getTitleHtml($obj) . '.' . $this->format;
        return File::getValidFilename($filename);
    }

    protected function getFilenameMultiple(int $count): string
    {
        $filename = $this->getTitleHtmlMultiple($count) . '.' . $this->format;
        return File::getValidFilename($filename);
    }

    /**
     * Для переопределения в классах потомках, если нужно добавить какие-то дополнительные данные для вывода на печать
     * в зависимости от полученного объекта
     * @return array
     */
    protected function getTplData(ObjAbstract $obj): array
    {
        $data = match ($this->templateFormat) {
            self::TEMPLATE_FORMAT_EXCEL => $this->getTplDataForExcel($obj),
            self::TEMPLATE_FORMAT_DOCX => $this->getTplDataForDocx($obj),
            default => $obj->getTplData(F::MODE_VIEW),
        };

        $data['_printForm'] = $this->key;
        $data = $this->getDialogDataFromRequest() + $data;

        return $data;
    }

    /**
     * Для переопределения в классах потомках, если нужно добавить какие-то дополнительные данные для вывода на печать
     * в зависимости от полученного объекта
     * @return array
     */
    protected function getTplDataForMultipleAsOne(array $objects): array
    {
        $data['_printForm'] = $this->key;
        $data = array_merge($data, $this->getDialogDataFromRequest());

        return $data;
    }

    protected function getTplDataForExcel(ObjAbstract $obj): array
    {
        // Используем простой метод $obj->getData(), чтобы получить данные ещё не преобразованные в формат Excel
        // (например, даты чисто в текстовом формате)
        // Преобразование в Excel форматы будет позже на этапе writeCellData()
        return $obj->getData();
    }

    protected function getTplDataForDocx(ObjAbstract $obj): array
    {
        // Для вывода в шаблон docx преобразуем все поля объекта в текстовое представление
        $data = $obj->getData();
        foreach ($obj->getFieldset() as $field) {
            $data[$field->key] = $obj->getFieldText($field->key);
        }
        return $data;
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
     * Можно переопределить в классе потомке для заполнения диалога печати начальными данными
     * @return array
     */
    public function getDataForAjaxDialog(ObjAbstract $obj): array
    {
        return [];
    }

    /**
     * Можно переопределить в классах потомках для заполнения диалогоа массовой печати начальными данными
     * @return array
     */
    public function getDataForAjaxDialogMultiple(array $ids, array $objects): array
    {
        return [];
    }

    public function proceedPrintMultipeAsOne(array $objects): void
    {
        $timer = new Timer();

        // Подготовим объект шаблонизатор нужного типа (Php, Excel, Docx)
        $templateFilepathAbs = Template::findfile($this->getTemplate($objects[0]));
        $templater = $this->createTemplater($templateFilepathAbs, $objects[0]);

        // Наполняем данными
        $data = $this->getTplDataForMultipleAsOne($objects);
        $this->writeData($templater, $data, $objects[0]);

        // Заготовим имя временного файла, куда запишем результат работы $templater
        $tmpDir = $this->createTmpDir();
        if (!$tmpDir) {
            Response::abort(500, 'Can\'t create temp dir');
            return;
        }

        // В качестве расширения выходного файла используем заданный $this->format
        $tmpFile = $tmpDir . '/result.' . $this->format;

        // Сохраняем во временный файл
        $this->save($templater, $tmpFile);

        $count = \count($objects);

        // Время отдать результат пользовтателю или переложить его в папку объекта
        $targetFilename = $this->getFilenameMultiple($count);

        // Если в настройках печатной формы указано отправлять файл в браузер пользователю,
        // то отправим только что сохранённый временный файл
        if ($this->format === self::FORMAT_HTML) {
            // html контент отправляем не как файл для скачивания, а как отдельную страницу
            readfile($tmpFile);
            Response::setMasterTemplate(null);
        } else {
            Response::streamFileFromFilesystem($tmpFile, $targetFilename);
        }

        Metric::record(Request::getModule() . '::proceedPrintMultipeAsOne ' . $this->key, $timer->getMs(),
            'Count: ' . $count);
    }

    public function hasFieldset(): bool
    {
        return $this->getFieldset()->count() > 0;
    }

    /**
     * @throws \Exception
     */
    public function proceedPrint(ObjAbstract $obj, string $targetDir): void
    {
        $timer = new Timer();

        // Подготовим объект шаблонизатор нужного типа (Php, Excel, Docx)
        $templateFilepathAbs = Template::findfile($this->getTemplate($obj));
        $templater = $this->createTemplater($templateFilepathAbs, $obj);

        // Наполняем данными
        $data = $this->getTplData($obj);
        $this->writeData($templater, $data, $obj);

        // Заготовим имя временного файла, куда запишем результат работы $templater
        $tmpDir = $this->createTmpDir();
        if (!$tmpDir) {
            Response::abort(500, 'Can\'t create temp dir');
            return;
        }

        // В качестве расширения выходного файла используем заданный $this->format
        $tmpFile = $tmpDir . '/result.' . $this->format;

        // Сохраняем во временный файл
        $this->save($templater, $tmpFile);

        // Время отдать результат пользовтателю или переложить его в папку объекта
        $targetFilename = $this->getFilename($obj);

        if ($this->isOutStream()) {
            // Если в настройках печатной формы указано отправлять файл в браузер пользователю,
            // то отправим только что сохранённый временный файл
            if ($this->format === self::FORMAT_HTML) {
                // html контент отправляем не как файл для скачивания, а как отдельную страницу
                readfile($tmpFile);
                Response::setMasterTemplate(null);
            } else {
                Response::streamFileFromFilesystem($tmpFile, $targetFilename);
            }
        }

        if ($this->isOutSave()) {

            try {

                // Скопируем временный файл в папку объекта (если $targetDir не усущетсует, она будет автоматически создана)
                File::fs()->copy($tmpFile, "$targetDir/$targetFilename", true);

            } catch (\Throwable $e) {
                // Не прерываем работу приложения, но сообщим об ошибке в rollbar
                \Light\ErrorHandler::reportException($e);
            }

        }

        Metric::record(Request::getModule() . '::proceedPrint ' . $this->key, $timer->getMs(), 'ID: ' . $obj->getId());
    }

    /**
     * @throws \Exception
     */
    public function proceedPrintMultiple(array $objects, array $targetDirs): void
    {
        $timer = new Timer();

        // Файл шаблона не зависит от объекта из массива $objects, подготовим переменную с путём к файлу сразу
        $firstObj = reset($objects);
        $templateFilepathAbs = Template::findfile($this->getTemplate($firstObj));

        // Подготавливаем временную директорию, куда будут сохранены все распечатанные файлы
        $tmpDir = $this->createTmpDir();
        if (!$tmpDir) {
            Response::abort(500, 'Can\'t create temp dir');
            return;
        }

        $tmpFiles = [];

        // При массовой печати будем запуска "асинхронное сохранение"
        // это значит, что под капотом печатная форма может запустить Symfony/Process,
        // результат которого нужно будет дождаться после окончания цикла
        $prcesses = [];

        /** @var ObjAbstract $obj */
        foreach ($objects as $i => $obj) {

            try {

                // Подготовим объект шаблонизатор нужного типа (Php, Excel, Docx)
                $templater = $this->createTemplater($templateFilepathAbs, $obj);

                // Наполняем данными
                $data = $this->getTplData($obj);
                $this->writeData($templater, $data, $obj);

                // Заготовим имя временного файла, куда запишем результат работы $templater
                // В качестве имени файла используем счётчик $i.
                // Расширение совпадает с выходным форматом
                $tmpFile = $tmpDir . "/$i." . $this->format;

                // Сохраняем во временный файл
                $proc = $this->saveAsync($templater, $tmpFile);
                if ($proc instanceof Process) {
                    $prcesses[] = $proc;
                    if (\count($prcesses) > 10) {
                        // Ограничим массив $prcesses 10 штуками, чтобы не запускать слишком много параллельных wkhtmltoppdf
                        // измерения показали, что более 10 потоков уже не дают прироста скорости
                        /** @var Process $firstProcessInQueue */
                        $firstProcessInQueue = array_shift($prcesses);
                        // Подождём его завершения, прежде чем двигаться дальше
                        // (а возможно, он уже успел завершиться, тогда wait отработает мгновенно)
                        $firstProcessInQueue->wait();
                    }
                }

                $tmpFiles[$i] = $tmpFile;

            } catch (\Throwable $e) {
                // Отлавливаем Exceptions, если какой-то файл не удалось распечатать
                // чтобы продолжить печать других файлов.
                // Тем не менее ошибку нужно зарегистрировать в rollbar
                \Light\ErrorHandler::reportException($e);
            }
        }

        // Дождёмся завершения всех асинхронных Symfony процессов (если такие есть)
        /** @var Process $proc */
        foreach ($prcesses as $proc) {
            try {
                $proc->wait();
            } catch (\Throwable $e) {
                \Light\ErrorHandler::reportException($e);
            }
        }

        // Накопили отрендеренные файлы во временной папке, пришло время их отдаль пользователю в форме одного файла
        if ($this->isOutStream()) {
            $count = \count($tmpFiles);
            // Если в настройках печатной формы указано отправлять файл в браузер пользователю,
            // то отправим только что сохранённый временный файл
            $concatinater = $this->createConcatinater();
            $resultFile = "$tmpDir/result." . $this->format;
            $concatinater->concat($tmpFiles, $resultFile, $this->getTitleHtmlMultiple($count));
            if ($this->format === self::FORMAT_HTML) {
                // если формат вывода HTML, то нужно отрендерить страницу в браузере, а не отправлять её файловм для скачивания
                readfile($resultFile);
                Response::setMasterTemplate(null);
            } else {
                Response::streamFileFromFilesystem($resultFile, $this->getFilenameMultiple($count));
            }
        }

        if ($this->isOutSave()) {
            // Переместим временные файлы в папки объектов
            foreach ($objects as $i => $obj) {

                try {

                    // Копируем временный файл в папку объекта
                    $tmpFile = $tmpFiles[$i] ?? '';
                    if ($tmpFile === '') {
                        // Для данного объекта почему-то не был создан временный файл с распечаткой
                        continue;
                    }

                    if (!is_file($tmpFile)) {
                        \Light\ErrorHandler::reportException(new \Exception("Не найден временный файл при массовой печати (будет пропущен) $tmpFile"));
                        continue;
                    }

                    $targetDir = $targetDirs[$i] ?? '';
                    if ($targetDir === '') {
                        // Для данного объекта почему-то не задана папка
                        continue;
                    }

                    $targetFilename = $this->getFilename($obj);
                    // Если $targetDir не существует, то она будет автоматически создана
                    File::fs()->copy($tmpFile, "$targetDir/$targetFilename", true);

                } catch (\Throwable $e) {
                    // Отлавливаем Exceptions, если какой-то файл не удалось скоприовать
                    // чтобы продолжить копирование других файлов.
                    // Тем не менее ошибку нужно зарегистрировать в rollbar
                    \Light\ErrorHandler::reportException($e);
                }
            }
        }

        Metric::record(Request::getModule() . '::proceedPrintMultiple ' . $this->key, $timer->getMs(),
            'Count: ' . \count($objects));
    }

    private function isOutStream(): bool
    {
        return $this->out & self::OUT_STREAM;
    }

    private function isOutSave(): bool
    {
        return $this->out & self::OUT_SAVE;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function createTmpDir(): string
    {
        $prefix = 'PrintForm_' . $this->key . '_' . date('Y-m-d_H_i_s');
        // Функция File::mkdirTmp автоматически запланирует удаление директории после окончания работы скрипта
        return File::mkdirTmp($prefix);
    }

    /**
     * @return TemplaterAbstract
     */
    private function createTemplater(string $templateFilepathAbs, ObjAbstract $obj): TemplaterAbstract
    {
        switch ($this->templateFormat) {
            case self::TEMPLATE_FORMAT_EXCEL:
                $titleExcelSheet = $this->getTitleExcelSheet($obj);
                return new TemplaterExcel($templateFilepathAbs, $titleExcelSheet, $obj->getFieldset(),
                    $this->excelTableOpts);

            case self::TEMPLATE_FORMAT_DOCX:
                return new TemplaterDocx($templateFilepathAbs);
        }

        $titleHtml = $this->getTitleHtml($obj);
        return new TemplaterPhp($templateFilepathAbs, $titleHtml);
    }

    /**
     * @throws \Exception
     */
    private function save(TemplaterAbstract $templateWriter, string $dest): void
    {
        switch ($this->format) {
            case self::FORMAT_HTML:
                $templateWriter->saveHtml($dest);
                break;

            case self::FORMAT_DOC:
                $templateWriter->saveDoc($dest);
                break;

            case self::FORMAT_DOCX:
                $templateWriter->saveDocx($dest);
                break;

            case self::FORMAT_PDF:
                $templateWriter->savePdf($dest, $this->pdfLandscape);
                break;

            case self::FORMAT_EXCEL:
                $templateWriter->saveExcel($dest);
                break;
        }
    }

    private function saveAsync(TemplaterAbstract $templateWriter, string $dest): ?Process
    {
        if ($this->format === self::FORMAT_PDF) {
            // Асинхронное сохранение доступно только при сохранении в PDF (и то, только из HTML)
            return $templateWriter->savePdfAsync($dest, $this->pdfLandscape);
        }

        // Все прочие форматы сохраняются синхронно
        $this->save($templateWriter, $dest);
        // Возвращаем null, чтобы сигнализировать вызывающему методу, что тут никакого асинхронного процесса нет
        // и дожидаться завершения нет смысла (всё уже синхронно завершено)
        return null;
    }

    protected function writeData(TemplaterAbstract $templater, array $data, ObjAbstract $obj)
    {
        $templater->writeData($data);
    }

    /**
     * @return ConcatinaterInterface
     * @throws \Exception
     */
    private function createConcatinater(): ConcatinaterInterface
    {
        return match ($this->format) {
            self::FORMAT_DOC, self::FORMAT_HTML => new ConcatinaterHtml(new Filesystem()),
            self::FORMAT_EXCEL => new ConcatinaterExcel(),
            self::FORMAT_PDF => new ConcatinaterPdf(),
            default => throw new \Exception('No Concatinater class for format ' . $this->format),
        };
    }

    public function isLinkBlank()
    {
        return ($this->out & self::OUT_STREAM) && $this->format === self::FORMAT_HTML;
    }

    public function getIcon()
    {
        return match ($this->format) {
            self::FORMAT_EXCEL => Ico::excel(),
            self::FORMAT_PDF => Ico::pdf(),
            self::FORMAT_DOC, self::FORMAT_DOCX => Ico::word(),
            default => Ico::print(),
        };
    }

}
