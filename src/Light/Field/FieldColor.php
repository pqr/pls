<?php

namespace Light\Field;

use Light\ApplicationAbstract;
use Light\Response;

/**
 * В режиме View выводит кнопку (img). В режиме Edit после изображения идёт скрытое input поле.
 * В обоих случаях, когда пользователь выбирает новый цвет, js код ищет ближайшего предка с классом color-container и
 * окрашивает его в выбранный цвет.
 * В режиме Edit выбранный цвет дополнительно сохраняется в скрытое input поле.
 *
 * Кнопка сама по себе не отправляет никаких ajax запросов, т.к. непонятно куда их отрпавлять. Однако, она генерирует
 * custom event Y.fire('currentBtn, bg);
 * Модуль ModuleAbstract перехватывает это событие в режиме List и в режиме View и отправляет ajax запрос на изменение
 * поля Color в базе.
 *
 * В других типах модулей (например в Cost-sheet'ах) требуется ручное программирование на стороне js, чтобы отправить
 * выбранный цвет на сервер через ajax
 *
 * @param string $source
 *
 * @method $this setSoruce(string $source)
 *
 * @method string getSource()
 */
class FieldColor extends FieldAbstract
{
    private static $colorPalleteInited = false;

    public $source;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->default = 0;
        $this->tableWidthHtml = 30;
        $this->tableAlign = 'center';
        //$this->modes = self::MODE_VIEW | self::MODE_EDIT | self::MODE_SUBMIT | self::MODE_LOG | self::MODE_MULTIPLEUPDATE;
    }

    private static function initColorPallete()
    {
        if (!self::$colorPalleteInited) {
            self::$colorPalleteInited = true;
            Response::addJsData(['ColorHints' => self::getColorHints()]);
        }
    }

    public function getText($value, array $data = [], $forTable = false)
    {
        return $value;
    }

    private function getHtmlImg(array $data)
    {
        $source = $this->source;
        if ($source && strpos($source, '{id}') !== false) {
            //Если среди данных был параметр ID и в строке source есть плейсхолдер {id}, нужно его заменить
            if (isset($data['ID'])) {
                $source = str_replace('{id}', $data['ID'], $source);
            } else {
                //Среди данных нет ID - вообще убираем source!
                $source = null;
            }
        }

        //Сформируем параметры для вставки в тег кнопки
        $tagParams = $source ? ' data-source="' . htmlspecialchars($source) . '"' : null;

        return '<i class="fa fa-picture-o colorpalette" ' . $tagParams . '></i>';
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        if ($forTable === 'total') {
            return '';
        }
        self::initColorPallete();
        return $this->getHtmlImg($data);
    }

    protected function getExcelInner($value, array $data)
    {
        return (int)$value;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        self::initColorPallete();

        $tdata = $this->getHtmlInputTplData($value, $data, $idPrefix);

        $colorCssClass = $value ? " color$value" : '';

        return '<div class="color-container' . $colorCssClass . '">'
            . $this->getHtmlImg($data)
            . '<input type="hidden" id="' . $tdata['editId']
            . '" name="' . $tdata['editName']
            . '" class="color-input" value="' . htmlspecialchars($tdata['value']) . '">'
            . '</div>';
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if ($value !== null) {
            $value = (int)$value;
        }

        return $value;
    }

    public function getHtmlForLog($value, array $data)
    {
        return '<span class="color' . (int)$value . ' color-span">' . $value . '</span>';
    }

    public function allowSqlWhereValue($value)
    {
        return (int)$value !== 0;
    }

    public static function getColorHints()
    {
        //Попробуем прочитать данные из system_params - там должен быть json
        $hints = ApplicationAbstract::getParam('ColorHints');
        if ($hints) {
            $hints = json_decode($hints, true);
        }

        //Если не удалось прочитать - преобразуем в массив принудительно
        if (!is_array($hints)) {
            $hints = [];
        }

        //Добавим значения по умолчанию для отсутствующих цветов
        for ($i = 1; $i <= 11; $i++) {
            $colorHintKey = 'ColorHint' . $i;
            if (!isset($hints[$colorHintKey])) {
                $hints[$colorHintKey] = '';
            }
        }

        return $hints;
    }
}
