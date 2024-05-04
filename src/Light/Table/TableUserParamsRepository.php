<?php


namespace Light\Table;


use Light\Db;

class TableUserParamsRepository
{
    private $isLoaded;

    // Кеширующие поля
    private $sortFieldKey = '';
    private $sortOrder = '';
    private $fieldsShow = '';
    private $fieldsWidth = [];
    private $showAll = false;

    public function __construct(private readonly string $tableNameForSettings, private readonly int $userId)
    {
    }

    /**
     * @return mixed
     */
    public function loadSortFieldKey()
    {
        $this->lazyLoadTableUserParams();
        return $this->sortFieldKey;
    }

    /**
     * @param mixed $sortFieldKey
     */
    public function saveSortFieldKey(string $sortFieldKey): void
    {
        $this->sortFieldKey = $sortFieldKey;
        $this->saveToDb('SortFieldKey', $sortFieldKey);
    }


    /**
     * @return string
     */
    public function loadSortOrder(): string
    {
        $this->lazyLoadTableUserParams();
        return $this->sortOrder;
    }

    public function saveSortOrder(string $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
        $this->saveToDb('SortOrder', $sortOrder);
    }

    /**
     * @return string
     */
    public function loadFieldsShow(): string
    {
        $this->lazyLoadTableUserParams();
        return $this->fieldsShow;
    }

    /**
     * @return array
     */
    public function loadFieldsWidth(): array
    {
        $this->lazyLoadTableUserParams();
        return $this->fieldsWidth;
    }

    public function saveFieldsShow(string $fieldsShow): void
    {
        $this->fieldsShow = $fieldsShow;
        $this->saveToDb('FieldsShow', $fieldsShow);
    }

    public function setFieldsWidth(array $fieldsWidth): void
    {
        $this->fieldsWidth = $fieldsWidth;
        $this->saveToDb('FieldsWidth', json_encode($fieldsWidth));
    }

    /**
     * @return bool
     */
    public function loadShowAll(): bool
    {
        $this->lazyLoadTableUserParams();
        return $this->showAll;
    }

    public function saveShowAll(bool $showAll): void
    {
        $this->showAll = $showAll;
        $this->saveToDb('ShowAll', $showAll ? 1 : 0);
    }

    private function lazyLoadTableUserParams(): void
    {
        if (!$this->isLoaded) {
            $params = Db::getRow(
                "SELECT SortFieldKey, SortOrder, FieldsShow, FieldsWidth, ShowAll 
                 FROM user_tableparam
                 WHERE TableName = @s AND CreatedBy = @i"
                , $this->tableNameForSettings
                , $this->userId
            );
            if ($params) {
                $this->sortFieldKey = (string)$params['SortFieldKey'];
                $this->sortOrder = (string)$params['SortOrder'];
                $this->fieldsShow = (string)$params['FieldsShow'];
                $this->showAll = (string)$params['ShowAll'];
                $this->fieldsWidth = json_decode((string) $params['FieldsWidth'], true);
                if (!is_array($this->fieldsWidth)) {
                    $this->fieldsWidth = [];
                }
            }

            $this->isLoaded = true;
        }
    }

    private function saveToDb(string $paramName, string $value): void
    {
        $query = "INSERT INTO user_tableparam (
                      CreatedBy,
                      TableName,
                      $paramName
                  ) VALUES (
                         $this->userId
                      , '" . Db::esc($this->tableNameForSettings) . "'
                      , '" . Db::esc($value) . "'

                  ) ON DUPLICATE KEY UPDATE $paramName = VALUES($paramName)";

        Db::query($query);
    }

}
