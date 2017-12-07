<?php

namespace Light;

use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldMoney;
use Light\Field\FieldMoneyWithCurrency;

/**
 * Класс для печати XLSX (Excel2007) файлов из шаблона
 * Предоставляет базовые функции:
 * - загрузить файл шаблона
 * - заменить все специальные переменные на данные
 * - сохранить результат в файл
 * - послать результат в поток
 */
class XlsWriter
{
    /**
     * @var \PHPExcel
     */
    private $_phpExcelObj;

    /*
    public static $cellDefaultStyle = [
        'font' => [
            'bold' => false,
            'color' => ['rgb' => '000000'],
            'size' => 12
        ],
        'fill' => [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => ['argb' => '00FFFFFF']
        ]
    ];
    */

    public static $cellHeaderStyle = [
        'font' => [
            'bold' => true,
            //'color' => ['rgb' => '000000'],
            //'size' => 12
        ],
        /*
        'fill' => [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => ['argb' => '00FFFFFF']
        ],*/
        'alignment' => [
            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' => true
        ]
    ];

    /*
    public static $cellBlackBorders = [
        'fill' => [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => ['argb' => '00FFFFFF']
        ],
        'borders' => [
            'allborders' => [
                'color' => ['rgb' => '000000'],
                'style' => PHPExcel_Style_Border::BORDER_THIN
            ]
        ]
    ];
    */

    public function __construct($filenameToLoad = null)
    {
        if ($filenameToLoad) {
            //Попытаемся загрузить файл
            $this->_phpExcelObj = \PHPExcel_IOFactory::load($filenameToLoad);
        } else {
            //Создаём пустую книгу
            $this->_phpExcelObj = new \PHPExcel();
            $this->_phpExcelObj->getProperties()
                ->setCreator(User::getName())
                ->setLastModifiedBy(User::getName());
        }
    }

    /**
     * Возвращает объект Writer (xls, xlsx) нужного типа в зависимости от имени файла
     * @param string $filename
     * @return \PHPExcel_Writer_IWriter
     */
    private function getWriter($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext === 'xlsx') {
            $format = 'Excel2007';
        } else {
            $format = 'Excel5';
        }

        return \PHPExcel_IOFactory::createWriter($this->_phpExcelObj, $format);
    }

    /**
     * Сохраняет результат в поток (в браузер)
     * Тип документа в зависимости от расширения: xls или xlsx
     * @param string $filename
     * @return void
     */
    public function send($filename)
    {
        $writerObj = $this->getWriter($filename);

        if (get_class($writerObj) === 'PHPExcel_Writer_Excel2007') {
            $mimetype = 'vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $mimetype = 'vnd.ms-excel';
        }

        Response::header('Content-Type: application/' . $mimetype);
        Response::header('Content-Disposition: attachment;filename="' . File::getValidFilename($filename) . '"');
        Response::header('Cache-Control: max-age=0');

        $writerObj->save('php://output');
    }

    /**
     * Сохраняет результат в файл
     * Тип документа в зависимости от расширения: xls или xlsx
     * @param string $filepath
     * @return void
     */
    public function save($filepath)
    {
        $filepathEnc = File::encode($filepath);
        $filename = pathinfo($filepathEnc, PATHINFO_BASENAME);
        $objWriter = $this->getWriter($filename);
        $objWriter->save($filepath);
    }

    public function setPhpExcelObj($phpExcelObj)
    {
        $this->_phpExcelObj = $phpExcelObj;
    }

    public function getPhpExcelObj()
    {
        return $this->_phpExcelObj;
    }

    /**
     * Заменяет ключевые слова в ячейках данными из ассоциативного массива
     * Ключевое слово в Excel должно быть обёрнуто в два процента %FieldName%
     * @param array $data
     * @param Fieldset $fieldset опциональный параметр для записи данных с учётом типов
     * @return void
     */
    public function replaceData(array $data, Fieldset $fieldset = null)
    {
        $phpExcel = $this->getPhpExcelObj();

        //Этот метод проходит в цикле по всем листам и в процессе необходимо менять ActiveSheet, чтобы правильно
        //работал метод $this->writeCellData(). Пожтому перед началом всех циктов запомним актиыный лист, который
        //был в момент вызова этого метода, чтобы не вносить побочных эффектов (по окончании циклов вернём активный лист
        //на место)
        $rememberActiveSheetIndex = $phpExcel->getActiveSheetIndex();

        $count = $phpExcel->getSheetCount();
        for ($sheetIndex = 0; $sheetIndex < $count; $sheetIndex++) {
            $sheet = $phpExcel->setActiveSheetIndex($sheetIndex);

            //Пройдёмся по всем ячейкам
            $highestRow = $sheet->getHighestRow(); // e.g. 10
            $highestColumn = $sheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            for ($currentRow = 1; $currentRow <= $highestRow; $currentRow++) {
                for ($currentCol = 0; $currentCol <= $highestColumnIndex; $currentCol++) {
                    $templateValue = $sheet->getCellByColumnAndRow($currentCol, $currentRow)->getValue();
                    //Проверим, есть ли внутри ячейки что-то связанное с процентамии (типа %fieldKey%)
                    preg_match_all("/%([\\w\\d]+)%/", $templateValue, $matches);
                    if (isset($matches[1][0])) {
                        //Что-то нашли! Сделаем замену: либо с учётом типа, если есть fieldset, либо тупую замену без учёта типа
                        if (count($matches[1]) === 1
                            && isset($data[$matches[1][0]])
                            && $data[$matches[1][0]] instanceof Table) {
                            // Если в ячейке ровно один плейсхолдер и он присутсвует в $data и является инстансом класса Table
                            // то сделаем подстановку таблицы с добавлением новых строк
                            /** @var Table $table */
                            $table = $data[$matches[1][0]];
                            $insertedRows = $this->insertTable($currentCol, $currentRow, $table, false);
                            $currentRow += $insertedRows;
                            $highestRow += $insertedRows;
                        } elseif ($fieldset !== null
                            && $matches[0][0] === $templateValue
                            && isset($data[$matches[1][0]])
                            && count($matches[1]) === 1
                            && $fieldset->hasField($matches[1][0])
                        ) {
                            //В этой ячейке ровно один плейсхолдер (и никаких дополнительных слов $matches[0][0] == $templateValue)
                            //и этот плейсхолдер известен в рамках fieldset!
                            $fieldKey = $matches[1][0];
                            $field = $fieldset[$fieldKey];
                            if ($field->getType() === F::TYPE_TEXT) {
                                //Запишем текст по строкам
                                $this->insertTextPerRows($currentCol, $currentRow, $data[$fieldKey]);
                            } else {
                                //Метод writeCellData не только записывает данные в ячейку, но и выставляет формат в зависимости от типа поля (для этого поле типа F передаём последним параметром)
                                $this->writeCellData(
                                    $currentCol,
                                    $currentRow,
                                    $data[$fieldKey],
                                    $data,
                                    $field
                                );
                            }
                        } else {
                            //Будем заменять более хитро
                            $newCellValue = $templateValue;
                            //Организуем цикл по найденным плейсхолдерам и будем заменять один за одним в $cellValue
                            foreach ($matches[1] as $fieldKey) {
                                //Проверим, есть ли вообще данные для данного поля?
                                if (isset($data[$fieldKey])) {
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
                                    $newCellValue = str_replace('%' . $fieldKey . '%', $replaceTo, $newCellValue);
                                }
                            }
                            $sheet->setCellValueByColumnAndRow($currentCol, $currentRow, $newCellValue);
                        }
                    }
                }
            }
        }

        $phpExcel->setActiveSheetIndex($rememberActiveSheetIndex);
    }

    /**
     * Заменяет плейсхолдеры $tableName$ таблицами
     *
     * Ограничения шаблонизатора: переменная с $tableName$ долна быть единственной на строке
     * Если таблица НЕ пуста - то будет добавлено нужное кол-во строк
     *
     * @param Table[] $tables
     */
    public function replaceDataWithTables(array $tables)
    {
        $phpExcel = $this->getPhpExcelObj();

        //Этот метод проходит в цикле по всем листам и в процессе необходимо менять ActiveSheet, чтобы правильно
        //работал метод $this->writeCellData(). Пожтому перед началом всех циктов запомним актиыный лист, который
        //был в момент вызова этого метода, чтобы не вносить побочных эффектов (по окончании циклов вернём активный лист
        //на место)
        $rememberActiveSheetIndex = $phpExcel->getActiveSheetIndex();

        $count = $phpExcel->getSheetCount();
        for ($sheetIndex = 0; $sheetIndex < $count; $sheetIndex++) {
            $phpExcel->setActiveSheetIndex($sheetIndex);
            $this->replaceDataWithTablesOnActiveSheet($tables);
        }

        $phpExcel->setActiveSheetIndex($rememberActiveSheetIndex);

    }

    public function replaceDataWithTablesOnActiveSheet(array $tables, $withHeader = true)
    {
        $sheet = $this->_phpExcelObj->getActiveSheet();

        //Пройдёмся по всем ячейкам в поисках плейсхолдеров $tableName$
        $highestRow = $sheet->getHighestRow(); // e.g. 10
        $highestColumn = $sheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

        for ($currentRow = 1; $currentRow <= $highestRow; $currentRow++) {
            for ($currentCol = 0; $currentCol <= $highestColumnIndex; $currentCol++) {
                $templateValue = (string)$sheet->getCellByColumnAndRow($currentCol, $currentRow)->getValue();

                //Проверим, есть ли внутри ячейки что-то связанное с долларами (типа $tableName$)
                preg_match_all('/\$([\w\d]+)\$/', $templateValue, $matches);
                if (isset($matches[1][0])) {
                    //Что-то нашли!
                    $tableName = $matches[1][0];

                    //Проверим, что такая таблица есть в массиве с таблицами для вставки
                    if (isset($tables[$tableName])) {

                        /** @var Table $table */
                        $table = $tables[$tableName];
                        $numRows = $this->insertTable($currentCol, $currentRow, $table, $withHeader);

                        //После вставки дополнительных строк, очевидно, увеличился параметр $highestRow
                        $highestRow += $numRows;
                        //Продолжим поиск других плейсхолдеров, однако, нужно перепрыгнуть сразу в конец
                        //только что вставленной таблицы, чтобы не искать плейсхолдеры внутри
                        $currentRow += $numRows;

                    }

                    //По условиям шаблонизатора, если в текущей строке был найден какой-то плейсхолдер - больше
                    //здесь ничего быть не может, нет смысла искать в остальных колонках направо
                    //делаем break из цикла по колонкам
                    break;

                }
            }
        }
    }

    public function insertTable($col, $row, Table $table, $withHeader)
    {
        $sheet = $this->_phpExcelObj->getActiveSheet();
        //Очистим ячейку от названия таблицы
        $sheet->setCellValueByColumnAndRow($col, $row, '');

        $numRows = $table->getTableDataCount();

        //Если число строк больше 0 - нужно добавить эти новые строки на лист
        //и записать таблицу
        if ($numRows > 0) {

            //Вставлять строки будем между текущей строкой (на которой обнаружили плейсхолдер) и следующей
            //таким образом на вновь вставленных строках будет скопирован стиль строки с плейсхолдером
            $sheet->insertNewRowBefore($row + 1, $numRows);

            //Теперь приступаем непосредственно к записи
            $this->writeTableData(
                $table->getTableData(),
                $table->getFieldset(),
                $col,
                $row,
                $withHeader,
                null,
                true
            );

        }

        return $numRows;
    }

    public function insertTextPerRows($col, $row, $text)
    {
        $ws = $this->_phpExcelObj->getActiveSheet();
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $ws->setCellValueExplicitByColumnAndRow($col, $row, $line, \PHPExcel_Cell_DataType::TYPE_STRING);
            $row++;
        }
    }

    public function writeTableHeader(Fieldset $fieldset, $startCol = 0, $headerRow = 1, $fromTemplate = false)
    {
        $ws = $this->_phpExcelObj->getActiveSheet();

        $currentCol = $startCol;

        $firsColLetter = \PHPExcel_Cell::stringFromColumnIndex($startCol);
        $lastColLetter = \PHPExcel_Cell::stringFromColumnIndex(count($fieldset) + $startCol - 1);

        //Запишем заголовки
        foreach ($fieldset as $field) {
            $headTxt = str_replace('<br>', ' ', strip_tags($field->getNameTable()));
            $ws->setCellValueExplicitByColumnAndRow($currentCol, $headerRow, $headTxt,
                \PHPExcel_Cell_DataType::TYPE_STRING);

            //В комментарии к ячейке вставим всплывающую подсказку title
            $title = $field->title;
            if ($title) {
                $ws->getCommentByColumnAndRow($currentCol, $headerRow)->getText()->createTextRun($title);
            }

            $currentCol++;
        }

        //Сделаем настройки стилей для строки заголовков
        $topRowRange = $firsColLetter . $headerRow . ':' . $lastColLetter . $headerRow;


        //Обернём заголовки в жирный и отцентрируем
        $ws->getStyle($topRowRange)->applyFromArray(self::$cellHeaderStyle);

        if (!$fromTemplate) {
            //Зафиксируем область заголовков для скролинга
            $ws->freezePane($firsColLetter . ($headerRow + 1));
        } else {
            $ws->getRowDimension($headerRow)->setRowHeight(-1);
        }
    }

    public function applyTableColumnStyles(
        Fieldset $fieldset,
        $startCol,
        $startDataRow,
        $lastRow,
        $fromTemplate = false
    ) {
        $ws = $this->_phpExcelObj->getActiveSheet();

        $currentCol = $startCol;
        /** @var \Light\Field\FieldAbstract $field */
        foreach ($fieldset as $field) {
            $colLetter = \PHPExcel_Cell::stringFromColumnIndex($currentCol);
            //Вычисляем Range в терминах Excel для текущей колонки, чтобы наложить стили ячеек (например A2:A315)
            $colRange = $colLetter . $startDataRow . ':' . $colLetter . $lastRow;

            //Устанавливаем выравнивание left|center|right
            if (strpos($field->key, 'CurrencyISO') !== false) {
                //Валюту всегда выравниваем по левому краю с добавлением indent
                $ws->getStyle($colRange)->getAlignment()->setHorizontal('left')->setIndent(1);
            } else {
                $tableAlign = $field->tableAlign;
                if ($tableAlign) {
                    $ws->getStyle($colRange)->getAlignment()->setHorizontal($tableAlign);
                } else {
                    // если ничего конкретного не определено, принудительно ставим left,
                    // чтобы автоматически определяемые числа не выравнивались вправо
                    $ws->getStyle($colRange)->getAlignment()->setHorizontal('left');
                }
            }

            //Установим ширину колонок, только если это не была запись таблицы в заранее подготовленный шаблон
            if (!$fromTemplate) {
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
     * Записывает данные на активный лист $this->_phpExcelObj
     * @param $data
     * @param Fieldset|F[] $fieldset
     * @param int $startCol номер колонки где должен расположиться верхний левый угол таблицы. Нумерация от 0
     * @param int $startRow номер строки, с которой должна начинаться таблица. Нумерация от 1
     * @param bool $header
     * @param callable $rowStyleFunction
     * @param bool $fromTemplate
     */
    public function writeTableData(
        array $data,
        Fieldset $fieldset,
        $startCol = 0,
        $startRow = 1,
        $header = true,
        callable $rowStyleFunction = null,
        $fromTemplate = false
    ) {
        $ws = $this->_phpExcelObj->getActiveSheet();

        $currentRow = $startRow;

        $firsColLetter = \PHPExcel_Cell::stringFromColumnIndex($startCol);
        $lastColLetter = \PHPExcel_Cell::stringFromColumnIndex(count($fieldset) + $startCol - 1);

        if ($header) {
            //Запишем заголовки
            $this->writeTableHeader($fieldset, $startCol, $startRow, $fromTemplate);
            $currentRow++;
        }

        //Запишем данные по строкам
        foreach ($data as $rowData) {
            $this->writeTableRow($rowData, $fieldset, $startCol, $currentRow, $rowStyleFunction, $fromTemplate);
            $currentRow++;
        }

        //Настройки колонок по стилям
        $lastRow = $currentRow - 1;
        $startDataRow = $startRow + ($header ? 1 : 0);
        $this->applyTableColumnStyles($fieldset, $startCol, $startDataRow, $lastRow, $fromTemplate);

        //Глобальные настройки для всего массива данных: выравнивание по вертикали "center"
        $globalRange = $firsColLetter . $startRow . ':' . $lastColLetter . $lastRow;
        $ws->getStyle($globalRange)->getAlignment()->setVertical('center');

        if ($header && !$fromTemplate) {
            $ws->setAutoFilter($globalRange);
        }
        $ws->setSelectedCellByColumnAndRow($startCol, $startRow);
    }

    public function writeTableRow(
        array $rowData,
        Fieldset $fieldset,
        $startCol,
        $currentRow,
        callable $rowStyleFunction = null,
        $fromTemplate = false
    ) {
        $ws = $this->_phpExcelObj->getActiveSheet();

        $firsColLetter = \PHPExcel_Cell::stringFromColumnIndex($startCol);
        $lastColLetter = \PHPExcel_Cell::stringFromColumnIndex(count($fieldset) + $startCol - 1);

        //Цикл по столбцам (полям таблицы)
        $currentCol = $startCol;

        $rowData = $fieldset->calcData($rowData, true);
        $hasTextField = false;

        foreach ($fieldset as $field) {
            $value = $rowData[$field->key];
            $this->writeCellData($currentCol, $currentRow, $value, $rowData, $field);
            $currentCol++;
            if ($fromTemplate) {
                $fieldType = $field->getType();
                if (
                    $fieldType === F::TYPE_TEXT ||
                    $fieldType === F::TYPE_HTML ||
                    ($field->groupconcat && strpos($value, "\n") !== false)
                ) {
                    $hasTextField = true;
                }
            }
        }

        //Можем проставить цвет\стиль для всей строки
        if ($rowStyleFunction) {
            $rowStyle = $rowStyleFunction($rowData, $currentRow);
            if ($rowStyle) {
                $rowRange = $firsColLetter . $currentRow . ':' . $lastColLetter . $currentRow;
                $ws->getStyle($rowRange)->applyFromArray($rowStyle);
            }
        }

        if ($fromTemplate && $hasTextField) {
            $ws->getRowDimension($currentRow)->setRowHeight(-1);
        }
    }

    public function writeCellData($col, $row, $value, array $rowData, F $field)
    {
        $ws = $this->_phpExcelObj->getActiveSheet();

        //Преобразуем данные преобразователем для Excel вывода
        //Здесь не нужно проверять наличие поля внутри $rowData - оно обязательно будет благодаря $fieldset->calcData($rowData, true);
        $val = $field->getExcel($value, $rowData);

        //Записываем данные в ячейку
        $type = $field->getType();

        //Если это числовой тип безе перевода строк внутри (т.е не groupconcat)
        //то можно записать обычной функцией setCellValueByColumnAndRow - Excel сам распознает число
        $noNewLine = strpos($val, "\n") === false;
        if ($noNewLine &&
            ($type === F::TYPE_INT
                || $type === F::TYPE_FLOAT
                || $type === F::TYPE_MONEY
                || $type === F::TYPE_DATE
                || $type === F::TYPE_DATE_TIME
                || $type === F::TYPE_MONTH
            )
        ) {
            $ws->setCellValueByColumnAndRow($col, $row, $val);
            if ($type == F::TYPE_DATE || $type == F::TYPE_DATE_TIME) {
                $ws->getStyleByColumnAndRow($col, $row)->getNumberFormat()->setFormatCode('dd.mm.yyyy');
            } elseif ($type === F::TYPE_MONEY) {
                //Проверим, есть ли у этого поля зафиксированная валюта?
                /** @var FieldMoney $field */
                $currencyIso = $field->currencyIso;
                if ($currencyIso) {
                    //Это поле Money с зафиксированной валютой
                    $format = '# ### ##0.00 [$' . $currencyIso . ']';
                } else {
                    //Это поля Money без определённой валюты
                    $format = "# ### ##0.00";
                }
                $ws->getStyleByColumnAndRow($col, $row)->getNumberFormat()->setFormatCode($format);
            }

        } elseif ($noNewLine && $type === F::TYPE_MONEY_WITH_CURRENCY) {
            if ($val) {
                list($currencyIso, $amount) = FieldMoneyWithCurrency::getAsTouple($val);
                $format = '# ### ##0.00 [$' . $currencyIso . ']';
                $ws->getStyleByColumnAndRow($col, $row)->getNumberFormat()->setFormatCode(
                    $format
                );
                $ws->setCellValueByColumnAndRow($col, $row, $amount);
            }
        } else {
            //Во всех остальных случаях явно укажем, что это строка, чтобы предотвратить перевод в число
            $ws->setCellValueExplicitByColumnAndRow(
                $col,
                $row,
                $val,
                \PHPExcel_Cell_DataType::TYPE_STRING
            );
        }

        //Если в значении есть перевод строки или это чисто текстовое поле - нужно сделать автоматический перенос слов
        if ($type === F::TYPE_HTML || $type === F::TYPE_TEXT || !$noNewLine) {
            $ws->getStyleByColumnAndRow($col, $row)->getAlignment()->setWrapText(1);
        }

        //Возможно, эту ячейку нужно как-то отдельно стилизовать? Првоерим параметр поля excelStyle
        //- там может быть готовый массив со стилями, либо функция, которую нужно вызвать, чтобы получить массив со стилями
        $cellStyle = $field->getExcelStyle($value, $rowData);
        if (is_array($cellStyle)) {
            $ws->getStyleByColumnAndRow($col, $row)->applyFromArray($cellStyle);
        }
    }

    /**
     * Записывает данные из активной переданной в качестве первого параметра на активный лист $this->_phpExcelObj
     * Отличие от предыдущего метода в том, что объект Table уже содержит в себе данные, Fieldset и метод для оформления
     * @param Table $table
     * @param int $startCol
     * @param int $startRow
     * @param bool $header
     * @param bool $fromTemplate
     */
    public function writeTable(
        Table $table,
        $startCol = 0,
        $startRow = 1,
        $header = true,
        $fromTemplate = false
    ) {
        $calculatedData = $table->getTableCalculatedData();

        //Подготовим параметр $rowStyleFunction - он возможен только если эта таблица использует trait TableExcelExportable
        if (method_exists($table, 'getTableRowDataExcelParams')) {
            $rowStyleFunction = [$table, 'getTableRowDataExcelParams'];
        } else {
            $rowStyleFunction = null;
        }

        $this->writeTableData(
            $calculatedData,
            $table->getFieldsetShowed(),
            $startCol,
            $startRow,
            $header,
            $rowStyleFunction,
            $fromTemplate
        );
    }
}
