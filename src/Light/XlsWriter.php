<?php

namespace Light;

use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldMoney;
use Light\Field\FieldMoneyWithCurrency;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;

/**
 * Класс для печати Xslx или Xls файлов из шаблона
 * Предоставляет базовые функции:
 * - загрузить файл шаблона
 * - заменить все специальные переменные на данные
 * - сохранить результат в файл
 * - послать результат в поток
 */
class XlsWriter
{
    const TABLES_WITH_HEADER = 0b0001;
    const FREEZE_PANES = 0b0010;
    const ADJUST_WIDTH = 0b0100;
    const AUTOFILTER = 0b1000;

    const ALLOPTS = self::TABLES_WITH_HEADER | self::FREEZE_PANES | self::ADJUST_WIDTH | self::AUTOFILTER;

    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    const STYLE_HEADER = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrap' => true
        ]
    ];

    const STYLE_SEPARATOR = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'f9f9cf'],
        ],
        'font' => [
            'bold' => true,
            'size' => 14
        ]
    ];

    const STYLE_TOTAL = [
        'font' => [
            'bold' => true,
        ]
    ];

    /**
     * XlsWriter constructor.
     * @param null $filenameToLoad
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function __construct($filenameToLoad = null)
    {
        if ($filenameToLoad) {
            // Попытаемся загрузить файл
            $this->spreadsheet = IOFactory::load($filenameToLoad);
        } else {
            // Создаём пустую книгу
            $this->spreadsheet = new Spreadsheet();
            $this->spreadsheet->getProperties()
                ->setCreator(User::getName())
                ->setLastModifiedBy(User::getName());
        }
    }

    /**
     * Возвращает объект Writer (xls, xlsx) нужного типа в зависимости от имени файла
     * @param string $filename
     * @return IWriter
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function getWriter(string $filename): IWriter
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext === 'xls') {
            $format = 'Xls';
        } else {
            $format = 'Xlsx';
        }

        return IOFactory::createWriter($this->spreadsheet, $format);
    }

    /**
     * Сохраняет результат в поток (в браузер)
     * Тип документа в зависимости от расширения: xls или xlsx
     * @param string $filename
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function send(string $filename): void
    {
        $writer = $this->getWriter($filename);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext === 'xls') {
            $mimetype = 'vnd.ms-excel';
        } else {
            $mimetype = 'vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        Response::header("Content-Type: application/$mimetype");
        Response::header('Content-Disposition: attachment;filename*=UTF-8\'\'' . rawurlencode(File::getValidFilename($filename)));
        Response::header('Cache-Control: max-age=0');

        $writer->save('php://output');

        Response::setMasterTemplate(null);
    }

    /**
     * @param string $filename
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function sendPdf(string $filename): void
    {
        $this->spreadsheet->getActiveSheet()->setShowGridLines(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf($this->spreadsheet);
        // PDF writer needs temporary directory
        $writer->setTempDir(LIGHT_DIR_TEMP);

        Response::header("Content-Type: application/pdf");
        Response::header('Content-Disposition: attachment;filename*=UTF-8\'\'' . rawurlencode(File::getValidFilename($filename)));
        Response::header('Cache-Control: max-age=0');

        $writer->save('php://output');

        Response::setMasterTemplate(null);
    }

    /**
     * Сохраняет результат в файл
     * Тип документа в зависимости от расширения: xls или xlsx
     * @param string $filepath
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function save(string $filepath): void
    {
        $filepathEnc = File::encode($filepath);
        $filename = pathinfo($filepathEnc, PATHINFO_BASENAME);
        $objWriter = $this->getWriter($filename);
        $objWriter->save($filepath);
    }


    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }

    /**
     * Заменяет ключевые слова в ячейках данными из ассоциативного массива
     * Ключевое слово в Excel должно быть обёрнуто в два процента %FieldName%
     * @param array $data
     * @param Fieldset|null $fieldset
     * @param int $tableOpts
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function replaceData(array $data, Fieldset $fieldset = null, $tableOpts = 0): void
    {
        //Этот метод проходит в цикле по всем листам и в процессе необходимо менять ActiveSheet, чтобы правильно
        //работал метод $this->writeCellData(). Пожтому перед началом всех циктов запомним актиыный лист, который
        //был в момент вызова этого метода, чтобы не вносить побочных эффектов (по окончании циклов вернём активный лист
        //на место)
        $rememberActiveSheetIndex = $this->spreadsheet->getActiveSheetIndex();

        $count = $this->spreadsheet->getSheetCount();
        for ($sheetIndex = 0; $sheetIndex < $count; $sheetIndex++) {
            $sheet = $this->spreadsheet->setActiveSheetIndex($sheetIndex);

            //Пройдёмся по всем ячейкам
            $highestRow = $sheet->getHighestRow(); // e.g. 10
            $highestColumn = $sheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // e.g. 5

            for ($currentRow = 1; $currentRow <= $highestRow; $currentRow++) {
                for ($currentCol = 1; $currentCol <= $highestColumnIndex; $currentCol++) {

                    $cellValue = $sheet->getCellByColumnAndRow($currentCol, $currentRow)->getValue();
                    // Проверим, есть ли внутри ячейки что-то связанное с процентамии (типа %fieldKey%)
                    preg_match_all("/%([\\w\\d]+)%/", $cellValue, $matches);
                    if (!isset($matches[1][0])) {
                        // Внутри текущей ячейки нет никаких паттернов для замены
                        continue;
                    }

                    if (count($matches[1]) === 1 && $matches[0][0] === $cellValue) {

                        // Внутри этой ячейки ровно один плейсхолдер, сделаем замену,
                        // если в массиве data есть соответсвующий элемент
                        $placeholder = reset($matches[1]);
                        if (array_key_exists($placeholder, $data)) {

                            $value = $data[$placeholder];
                            if ($value instanceof Table) {
                                // Если текщий плейсхолдер ссылается на объект таблицы в массиве $data,
                                // то вставляем как таблицу специальным методом insertTable
                                $table = $value;
                                $insertedRows = $this->insertTable($currentCol, $currentRow, $table, $tableOpts);
                                $currentRow += $insertedRows;
                                $highestRow += $insertedRows;

                                // переходим к следующей ячейке
                                continue;
                            }

                            // Если же это не таблица, то проверим, возможно описание поля есть в Fieldset?
                            if ($fieldset !== null && $fieldset->hasField($placeholder)) {
                                $fieldKey = $placeholder;
                                $field = $fieldset[$fieldKey];
                                if ($field->getType() === F::TYPE_TEXT) {
                                    //Запишем текст по строкам
                                    $this->writeTextPerRows($currentCol, $currentRow, $value);
                                } else {
                                    // Метод writeCellData не только записывает данные в ячейку,
                                    // но и выставляет формат в зависимости от типа поля (для этого поле типа F передаём последним параметром)
                                    $this->writeCellDataByColumnAndRow(
                                        $currentCol,
                                        $currentRow,
                                        $value,
                                        $data,
                                        $field
                                    );
                                }

                                // переходим к следующей ячейке
                                continue;
                            }

                            // Наконец, похоже этот плейсхолдер ссылкается на сырое значение (без описания в Fieldset)
                            // Просто записываем значение без какого-либо форматирования
                            $sheet->setCellValueByColumnAndRow($currentCol, $currentRow, $value);

                        }

                    } else {

                        // Если в ячейке несколько плейсхолдеров, то нужно заменять каждый по отдельности
                        // при этом мы не можем применить форматирование на всю ячейку
                        $newCellValue = $cellValue;
                        // Организуем цикл по найденным плейсхолдерам и будем заменять один за одним в $cellValue
                        foreach ($matches[1] as $fieldKey) {
                            //Проверим, есть ли вообще данные для данного поля?
                            if (!isset($data[$fieldKey])) {
                                continue;
                            }

                            //Проверим, есть ли этот плейсхолдер в $fieldset?
                            if ($fieldset !== null && $fieldset->hasField($fieldKey)) {
                                //Перед заменой нужно преобразовать данные в соответвие с типом
                                /** @var FieldAbstract $field */
                                $field = $fieldset[$fieldKey];
                                $type = $field->getType();
                                if ($type === F::TYPE_DATE || $type === F::TYPE_DATE_TIME) {
                                    $replaceTo = FieldDate::beautify($data[$fieldKey]);
                                } else {
                                    $replaceTo = $field->getExcel($data[$fieldKey], $data);
                                }
                            } else {
                                //Если в fieldset нет типа для этого поля, то подставляем из массива $data как есть
                                $replaceTo = $data[$fieldKey];
                            }
                            $newCellValue = str_replace("%$fieldKey%", $replaceTo, $newCellValue);
                        }

                        // Результат последовательно замены плейсхолдеров на значения из $data запишем в итоге в ячейку
                        $sheet->setCellValueByColumnAndRow($currentCol, $currentRow, $newCellValue);

                    }
                }
            }
        }

        // Возвращаем сохранённое ранее значение активного листа
        $this->spreadsheet->setActiveSheetIndex($rememberActiveSheetIndex);
    }

    /**
     * Вставляет таблицу по указанным координатам, добавляя новые строки (раздвигая шаблон по вертикали)
     *
     * @param int $col
     * @param int $row
     * @param Table $table
     * @param int $tableOpts битовая маска опция для таблицы
     * @return int число новых вставленных строк в шаблон
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function insertTable(int $col, int $row, Table $table, $tableOpts = 0): int
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        //Очистим ячейку от названия таблицы
        $sheet->setCellValueByColumnAndRow($col, $row, '');

        $insertedRows = 0;

        $numRows = $table->getTableDataCount();
        if ($numRows > 0 || $tableOpts & self::TABLES_WITH_HEADER) {

            //Подготовим параметр $rowStyleFunction - он возможен только если эта таблица использует trait TableExcelExportable
            if (method_exists($table, 'getTableRowDataExcelParams')) {
                $rowStyleFunction = [$table, 'getTableRowDataExcelParams'];
            } else {
                $rowStyleFunction = null;
            }

            //Теперь приступаем непосредственно к записи
            $dataCalculated = $table->getTableDataCalculated();
            $dataTotal = $table->getTableTotal($dataCalculated);

            // Подготовим пустые строки под таблицу
            // Вставлять строки будем между текущей строкой (на которой обнаружили плейсхолдер) и следующей
            // таким образом на вновь вставленных строках будет скопирован стиль строки с плейсхолдером
            // Как минимум нужно вставить число строк на основе данных, минус одна (одна строка будет переиспользована)
            $insertedRows = $numRows - 1;
            // Если нужо вывести заголовок, это ещё плюс одна строка
            if ($tableOpts & self::TABLES_WITH_HEADER) {
                $insertedRows++;
            }
            // Если есть строка total, это ещё +1
            if ($dataTotal) {
                $insertedRows++;
            }
            // Если накопилось какое-то число строк для вставки, то сделаем эту вставку новых пустых строк
            // куда позже будут записаны данные таблицы
            if ($insertedRows) {
                $sheet->insertNewRowBefore($row + 1, $insertedRows);
            }

            // Записываем данные таблицы
            $this->writeTableData(
                $col,
                $row,
                $dataCalculated,
                $dataTotal,
                $table->getFieldsetShowed(),
                $rowStyleFunction,
                $tableOpts
            );

        }

        return $insertedRows;
    }

    /**
     * Пишет текст начиная со строки $row и дальше вниз каждую линию на новую строку
     * При этом новые строки НЕ добавляются в шаблон, т.е. этот метод потенциально может перезаписать данные ниже
     * @param int $col
     * @param int $row
     * @param string $text
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function writeTextPerRows(int $col, int $row, string $text): void
    {
        $ws = $this->spreadsheet->getActiveSheet();
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $ws->setCellValueExplicitByColumnAndRow($col, $row, $line, DataType::TYPE_STRING);
            $row++;
        }
    }

    /**
     * Пишет заголовок таблицы по указанным координатам (набор полей берётся из $fieldset)
     *
     * @param int $col
     * @param int $row
     * @param Fieldset $fieldset
     * @param int $tableOpts битовая маска, в этом методе используется только одна опция XlsWriter::FREEZE_PANES
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function writeTableHeader(int $col, int $row, Fieldset $fieldset, $tableOpts = 0): void
    {
        $ws = $this->spreadsheet->getActiveSheet();

        $currentCol = $col;

        //Запишем заголовки
        foreach ($fieldset as $field) {
            $headTxt = str_replace('<br>', ' ', strip_tags($field->getNameTable()));
            $ws->setCellValueExplicitByColumnAndRow($currentCol, $row, $headTxt, DataType::TYPE_STRING);

            // В комментарии к ячейке вставим всплывающую подсказку title
            $title = $field->title;
            if ($title) {
                $ws->getCommentByColumnAndRow($currentCol, $row)->getText()->createTextRun($title);
            }

            $currentCol++;
        }

        // Обернём заголовки в жирный и отцентрируем
        $ws->getStyleByColumnAndRow($col, $row, count($fieldset) + $col - 1, $row)
            ->applyFromArray(self::STYLE_HEADER)
            ->getAlignment()->setWrapText(1);

        if ($tableOpts & self::FREEZE_PANES) {
            //Зафиксируем область заголовков для скролинга
            $ws->freezePaneByColumnAndRow($col, $row + 1);
        } else {
            $ws->getRowDimension($row)->setRowHeight(-1);
        }
    }

    /**
     * Применяет стили описанные в $fieldset к колонкам таблицы начиная со строки $row и заканчивая строкой $lastRow
     * @param int $col
     * @param int $row
     * @param int $lastRow
     * @param Fieldset $fieldset
     * @param int $tableOpts битовая маска, в этом методе используется только одна опция XlsWriter::ADJUST_WIDTH
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function applyTableColumnStyles(
        int $col,
        int $row,
        int $lastRow,
        Fieldset $fieldset,
        $tableOpts = 0
    ): void {
        $ws = $this->spreadsheet->getActiveSheet();

        $currentCol = $col;
        /** @var \Light\Field\FieldAbstract $field */
        foreach ($fieldset as $field) {
            $colLetter = Coordinate::stringFromColumnIndex($currentCol);
            //Вычисляем Range в терминах Excel для текущей колонки, чтобы наложить стили ячеек (например A2:A315)
            $colRange = $colLetter . $row . ':' . $colLetter . $lastRow;

            //Устанавливаем выравнивание left|center|right
            if (strpos($field->key, 'CurrencyISO') !== false) {
                //Валюту всегда выравниваем по левому краю с добавлением indent
                $ws->getStyle($colRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            } else {
                $tableAlign = $field->tableAlign;
                if ($tableAlign) {
                    $ws->getStyle($colRange)->getAlignment()->setHorizontal($tableAlign);
                } else {
                    // если ничего конкретного не определено, принудительно ставим left,
                    // чтобы автоматически определяемые числа не выравнивались вправо
                    $ws->getStyle($colRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
            }

            //Установим ширину колонок, только если это не была запись таблицы в заранее подготовленный шаблон
            if ($tableOpts & self::ADJUST_WIDTH) {
                $width = $field->tableWidthExcel;
                if ($width && $width !== 'auto') {
                    $ws->getColumnDimension($colLetter)->setWidth($width);
                } elseif ($width === 'auto') {
                    $ws->getColumnDimension($colLetter)->setAutoSize(true);
                }
            }

            $currentCol++;
        }
    }

    /**
     * Записывает данные таблицы, но при этом не вставляет новые строки в шаблон
     * Т.е. потенциально может перезаписать данные находящиеся в шаблоне после строки $row
     *
     * Если нужно вставлять данные таблицы с добавлением новых строк, используйте метод insertTable()
     *
     * @param int $col
     * @param int $row
     * @param array $dataCalculated
     * @param array $dataTotal
     * @param Fieldset $fieldset
     * @param callable|null $rowStyleFunction
     * @param int $tableOpts битовая маска, определяет нужно ли писать заголовок таблицы, фиксировать первую строку, накладывать автофильтры, менять ширину колонок
     * @return int $insertedRows
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function writeTableData(
        int $col,
        int $row,
        array $dataCalculated,
        array $dataTotal,
        Fieldset $fieldset,
        callable $rowStyleFunction = null,
        $tableOpts = 0
    ): int {

        $ws = $this->spreadsheet->getActiveSheet();

        $currentRow = $row;

        if ($tableOpts & self::TABLES_WITH_HEADER) {
            //Запишем заголовки
            $this->writeTableHeader($col, $currentRow, $fieldset, $tableOpts);
            $currentRow++;
        }

        // Запишем данные по строкам
        foreach ($dataCalculated as $rowNum => $rowDataCalculated) {
            $this->writeTableRow($col, $currentRow, $rowDataCalculated, $fieldset, $rowStyleFunction, $rowNum);
            $currentRow++;
        }

        $lastCol = count($fieldset) + $col - 1;

        if ($dataTotal) {
            // Есть какая-то total строка, запишем и её
            $this->writeTableRow($col, $currentRow, $dataTotal, $fieldset, $rowStyleFunction, 'total');
            $ws->getStyleByColumnAndRow($col, $currentRow, $lastCol, $currentRow)
                ->applyFromArray(self::STYLE_TOTAL);
            $currentRow++;
        }

        // Настройки колонок по стилям
        $lastRow = $currentRow - 1;
        $startDataRow = $row + ($tableOpts & self::TABLES_WITH_HEADER ? 1 : 0);
        $this->applyTableColumnStyles($col, $startDataRow, $lastRow, $fieldset, $tableOpts);

        // Глобальные настройки для всего массива данных: выравнивание по вертикали "center"
        $ws->getStyleByColumnAndRow($col, $row, $lastCol, $lastRow)
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        if ($tableOpts & self::AUTOFILTER) {
            $ws->setAutoFilterByColumnAndRow($col, $row, $lastCol, $lastRow);
        }
        $ws->setSelectedCellByColumnAndRow($col, $row);

        $insertedRows = $lastRow - $row + 1;
        return $insertedRows;
    }

    /**
     * Записывает данные в строку, форматируя ячейки в соответвие с типами полей описанными в $fieldset
     * @param int $col
     * @param int $row
     * @param array $rowDataCalculated
     * @param Fieldset $fieldset
     * @param callable|null $rowStyleFunction
     * @param null $rowNum
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function writeTableRow(
        int $col,
        int $row,
        array $rowDataCalculated,
        Fieldset $fieldset,
        callable $rowStyleFunction = null,
        $rowNum = null
    ): void {
        $ws = $this->spreadsheet->getActiveSheet();

        // Цикл по столбцам (полям таблицы)
        $currentCol = $col;

        $hasTextField = false;

        foreach ($fieldset as $field) {
            $value = $rowDataCalculated[$field->key] ?? '';
            $this->writeCellDataByColumnAndRow($currentCol, $row, $value, $rowDataCalculated, $field);
            $currentCol++;

            $fieldType = $field->getType();
            if (
                $fieldType === F::TYPE_TEXT ||
                $fieldType === F::TYPE_HTML ||
                ($field->groupconcat && strpos($value, "\n") !== false)
            ) {
                $hasTextField = true;
            }
        }

        // Можем проставить цвет\стиль для всей строки
        if ($rowStyleFunction) {
            // Для $rowStyleFunction вторым параметром нужно передать номер строки в текущем массиве данных
            // Это значение обычно приходит извне, но если оно не передано (null),
            // то используем текущий номер строки в самом Excel ($row)
            $rowNum = $rowNum ?? $row;
            $rowStyle = $rowStyleFunction($rowDataCalculated, $rowNum);
            if ($rowStyle) {
                $ws->getStyleByColumnAndRow($col, $row, count($fieldset) + $col - 1, $row)
                    ->applyFromArray($rowStyle);
            }
        }

        if ($hasTextField) {
            $ws->getRowDimension($row)->setRowHeight(-1);
        }
    }

    /**
     * Записывает значение одной ячейки ($value) применяя форматирование описанное в $field
     *
     * @param int $col
     * @param int $row
     * @param $value
     * @param array $rowDataCalculated массив всех данных строки может пригодится при определении форматирования для данной ячейки
     * @param F $field
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function writeCellDataByColumnAndRow(int $col, int $row, $value, array $rowDataCalculated, F $field): void
    {
        $columnLetter = Coordinate::stringFromColumnIndex($col);
        $coordinate = $columnLetter . $row;

        $this->writeCellData($coordinate, $value, $rowDataCalculated, $field);
    }

    /**
     * @param string $coordinate
     * @param $value
     * @param array $rowDataCalulated
     * @param F $field
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function writeCellData(string $coordinate, $value, array $rowDataCalulated, F $field): void
    {
        $ws = $this->spreadsheet->getActiveSheet();

        // Преобразуем данные преобразователем для Excel вывода
        // Здесь не нужно проверять наличие поля внутри $rowData - оно обязательно будет благодаря $fieldset->calcData($rowData, true);
        $excelVal = $field->getExcel($value, $rowDataCalulated);

        //Записываем данные в ячейку
        $type = $field->getType();

        //Если это числовой тип безе перевода строк внутри (т.е не groupconcat)
        //то можно записать обычной функцией setCellValueByColumnAndRow - Excel сам распознает число
        $noNewLine = strpos($excelVal, "\n") === false;
        if ($noNewLine &&
            ($type === F::TYPE_INT
                || $type === F::TYPE_FLOAT
                || $type === F::TYPE_MONEY
                || $type === F::TYPE_DATE
                || $type === F::TYPE_DATE_TIME
                || $type === F::TYPE_MONTH
            )
        ) {
            $ws->setCellValue($coordinate, $excelVal);
            if ($type == F::TYPE_DATE || $type == F::TYPE_DATE_TIME) {
                $ws->getStyle($coordinate)->getNumberFormat()->setFormatCode('dd.mm.yyyy');
            } elseif ($type === F::TYPE_MONEY) {
                // Проверим, есть ли у этого поля зафиксированная валюта?
                /** @var FieldMoney $field */
                $currencyIso = $field->currencyIso;
                if ($currencyIso) {
                    // Это поле Money с зафиксированной валютой
                    $format = '# ### ##0.00 [$' . $currencyIso . ']';
                } else {
                    // Это поля Money без определённой валюты
                    $format = "# ### ##0.00";
                }
                $ws->getStyle($coordinate)->getNumberFormat()->setFormatCode($format);
            }

        } elseif ($type === F::TYPE_MONEY_WITH_CURRENCY) {
            if ($excelVal) {
                if ($noNewLine) {
                    [$currencyIso, $amount] = FieldMoneyWithCurrency::getAsTouple($excelVal);
                    $format = '# ### ##0.00 [$' . $currencyIso . ']';
                    $ws->getStyle($coordinate)->getNumberFormat()->setFormatCode(
                        $format
                    );
                    $ws->setCellValue($coordinate, $amount);
                } else {
                    $lines = explode("\n", $excelVal);
                    foreach ($lines as &$lineText) {
                        $lineText = strip_tags(FieldMoneyWithCurrency::beautify($lineText));
                    }
                    unset($lineText);
                    $beautyVal = implode("\n", $lines);
                    $ws->setCellValue($coordinate, $beautyVal);
                }
            }
        } else {
            // Во всех остальных случаях явно укажем, что это строка, чтобы предотвратить перевод в число
            $ws->setCellValueExplicit($coordinate, $excelVal, DataType::TYPE_STRING);
        }

        // Если в значении есть перевод строки или это чисто текстовое поле - нужно сделать автоматический перенос слов
        if ($type === F::TYPE_HTML || $type === F::TYPE_TEXT || !$noNewLine) {
            $ws->getStyle($coordinate)->getAlignment()->setWrapText(1);
        }

        // Возможно, эту ячейку нужно как-то отдельно стилизовать? Првоерим параметр поля excelStyle
        // - там может быть готовый массив со стилями, либо функция, которую нужно вызвать, чтобы получить массив со стилями
        $cellStyle = $field->getExcelStyle($value, $rowDataCalulated);
        if (is_array($cellStyle)) {
            $ws->getStyle($coordinate)->applyFromArray($cellStyle);
        }
    }
}
