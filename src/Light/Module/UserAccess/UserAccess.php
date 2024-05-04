<?php

namespace Light\Module\UserAccess;

use Light\ApplicationAbstract;
use Light\Db;
use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\ModuleRegister;
use Light\Response;
use Light\User;

class UserAccess extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->allowAdd = false;
        $this->allowDelete = false;
        $this->allowEdit = false;
        $this->allowSort = false;
        $this->allowPagination = false;

        $this->addFields(
            F::createSelect('UserID', [
                'name' => 'User',
                'tableWidthHtml' => 160,
                'options' => 'SELECT ID, Name, Active FROM user ORDER BY Name',
                'select2' => true,
                'avatar' => true,
                'empty' => false,
                'nobr' => true,
                'editWidht' => 'auto',
            ]),
            self::moduleField(),
            self::rightField(),
            self::specialAccessField()->setGroupconcat(true)
        );
    }

    protected function initFilters()
    {
        $this->addFilters(
            CommonFields::filterResponsibleId(),
            self::moduleField(),
            self::rightField(),
            self::specialAccessField()
        );

        $this->filtersLayout = [
            [
                'FilterResponsibleID',
                'Module',
                'Right',
            ],
            [
                'SpecialAccess'
            ]
        ];
    }

    private static function getModules(): array
    {
        static $moduleOptions = [];
        if (!$moduleOptions) {
            foreach (ModuleRegister::$modules as $key => $module) {
                $moduleOptions[$key] = $module['name'];
            }
        }
        return $moduleOptions;
    }

    private static function moduleField(): F
    {
        return F::createSelect('Module', [
            'name' => 'Модуль',
            'options' => self::getModules(),
            'select2' => true
        ]);
    }

    private static function rightField(): F
    {
        $rightOptions = [
            0 => ['text' => 'Deny', 'cssClass' => 'bad'],
            1 => ['text' => 'Read', 'cssClass' => 'read'],
            2 => ['text' => 'Write', 'cssClass' => 'good'],
            3 => ['text' => 'Write + Delete', 'cssClass' => 'good bold']
        ];

        return F::createSelect2Multiple('Right', [
            'name' => 'Права',
            'options' => $rightOptions
        ]);
    }

    private static function specialAccessField(): F
    {
        $specialAccess = ApplicationAbstract::$app->getSpecialAccessList();
        $specialAccessOptions = [];
        $specialAccessRelations = [];
        foreach ($specialAccess as $access) {
            $specialAccessOptions[$access[1]] = $access[2];
            $specialAccessRelations[] = ['Module' => $access[0], 'SpecialAccess' => $access[1]];
        }

        return F::createSelectChild('SpecialAccess', [
            'name' => 'Специальные права',
            'options' => $specialAccessOptions,
            'parentFieldKeys' => 'Module',
            'relations' => $specialAccessRelations,
            'select2' => true
        ]);
    }

    protected function tableLoadData()
    {
        if ($this->getFiltersSqlWhere() === '1') {
            Response::sendToast('warning', 'Выберите фильтр');
            return;
        }

        $filterUserId = $this->getFilterValue('FilterResponsibleID');
        $filterModule = $this->getFilterValue('Module');
        $filterRight = $this->getFilterValue('Right');
        $filterSpecialAccess = $this->getFilterValue('SpecialAccess');

        $query = 'SELECT ID AS UserID FROM user WHERE Active = 1';
        if ($filterUserId !== '%') {
            $query .= ' AND ID = ' . $filterUserId;
        }
        $query .= ' ORDER BY Name';

        $userData = Db::getAll($query);

        $data = [];

        $specialAccess = ApplicationAbstract::$app->getSpecialAccessList();

        foreach ($userData as $row) {
            foreach (self::getModules() as $module => $moduleName) {

                if ($filterModule !== '%' && $filterModule !== $module) {
                    continue;
                }

                $row['Module'] = $moduleName;

                $right = User::getAccess($module, $row['UserID']);

                if ($filterRight !== '%' && !str_contains($filterRight, '|' . $right . '|')) {
                    continue;
                }

                $row['Right'] = $right;

                $saList = array_filter($specialAccess, static fn($access) => $access[0] === $module);

                $saIndexes = array_column($saList, 1);

                if ($filterSpecialAccess !== '%' && (!in_array($filterSpecialAccess, $saIndexes) || !User::hasSpecialAccess($filterSpecialAccess, $row['UserID']))) {
                    continue;
                }

                $sa = [];

                foreach ($saList as $saItem) {
                    if (User::hasSpecialAccess($saItem[1], $row['UserID'])) {
                        $sa[] = $saItem[2];
                    }
                }

                $row['SpecialAccess'] = implode("\n", $sa);

                $data[] = $row;
            }
        }

        $this->tableData = $data;
    }
}
