<?php
namespace Light\Field;

/**
 * @method $this setRows(int $rows)
 * @method int getRows()
 */
class FieldText extends FieldAbstract
{
    public $rows = 4;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->default = '';
        $this->tableWidthExcel = 50;
        $this->editWidth = 'default';
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        return str_replace("\n", '<br>', htmlspecialchars($value));
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $value = (string)$value;
        $tdata = $this->getHtmlInputTplData($value, $data, $idPrefix);

        $rows = $this->rows;
        if ($rows) {
            //Специальный хак для FF - указать высоту в em единицах внутри style
            if (strpos($tdata['tagParams'], 'height:') === false
            ) {
                //в $tagParams нет явного указания height, значит можем переопределить сами
                $height = ($rows * 1.5) . 'em';
                if (strpos($tdata['tagParams'], 'style="') === false) {
                    //В $tagParams вообще нету параметра style, определим его
                    $tdata['tagParams'] .= ' style="height: ' . $height . ';"';
                } else {
                    //В $tagParams есть параметр style, height нужно добавить внутрь
                    $tdata['tagParams'] = str_replace(
                        'style="',
                        'style="height: ' . $height . ';',
                        $tdata['tagParams']
                    );
                }
            }

            $tdata['tagParams'] .= ' rows="' . $rows . '"';
        }

        return '<textarea data-type="' . $tdata['type'] . '" name="' . $tdata['editName'] . '" id="' . $tdata['editId'] .
        '"' . $tdata['cssClass'] . $tdata['tagParams'] . '>' . htmlspecialchars($tdata['value']) . '</textarea>';
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if ($value !== null && is_string($value)) {
            $value = str_replace("\r", '', trim($value));
        }

        return $value;
    }

    public function getSqlWhereInner($filterValue)
    {
        return "{fieldKey} LIKE '%{value}%'";
    }
}
