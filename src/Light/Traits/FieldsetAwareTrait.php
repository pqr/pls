<?php
namespace Light\Traits;

use Light\Field\FieldAbstract as F;
use Light\Fieldset;

trait FieldsetAwareTrait
{
    /**
     * @return Fieldset|F[]
     */
    abstract public function getFieldset();

    public function addField(F $field, $clone = false)
    {
        $this->getFieldset()->addField($field, $clone);
    }

    public function addFields(...$fields)
    {
        $this->getFieldset()->addFields(...$fields);
    }

    public function addFieldsUpdatedBy()
    {
        $this->getFieldset()->addFieldsUpdatedBy();
    }

    public function addFieldsCreatedBy()
    {
        $this->getFieldset()->addFieldsCreatedBy();
    }

    public function createField($fieldKey, $type, array $params = [])
    {
        $this->getFieldset()->createField($fieldKey, $type, $params);
    }

    /**
     * @param string $fieldKey
     * @return F
     */
    public function getField(string $fieldKey)
    {
        return $this->getFieldset()->getField($fieldKey);
    }

    public function hasField(string $fieldKey)
    {
        return $this->getFieldset()->hasField($fieldKey);
    }

    public function getDataFromRequest(array $request)
    {
        return $this->getFieldset()->getDataFromRequest($request);
    }

    public function getSqlSelect(array $fieldsShow = null)
    {
        return $this->getFieldset()->getSqlSelect($fieldsShow);
    }
}
