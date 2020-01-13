<?php

namespace Light\Codeception;

use Light\Db;
use Light\Field\FieldAbstract;
use Light\Field\FieldCheckbox;
use Light\Field\FieldDate;
use Light\Field\FieldSelect;
use Light\File;
use Light\FilesFolder;
use Light\ModuleBase\ModuleAbstract;
use Light\ModuleRegister;
use Light\ObjAbstract;
use Light\PrintForm\PrintForm;
use Light\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

abstract class ModuleCestAbstract
{
    protected $minIdFromDb;

    protected function getDbTable()
    {
        $moduleInstance = $this->getModuleInstance();
        return $moduleInstance->getDbTable();
    }

    /**
     * Можно переопределить в классе потомке
     * @return string
     * @throws \Exception
     */
    protected function getTestObjIdSqlWhere(): string
    {
        return '1';
    }

    /**
     * Можно переопределить в классе потомке
     * @return int
     * @throws \Exception
     */
    protected function getTestObjId()
    {
        if ($this->minIdFromDb === null) {
            $dbtable = $this->getDbTable();
            $this->minIdFromDb = (int)Db::getOne("SELECT min(ID) FROM $dbtable WHERE " . $this->getTestObjIdSqlWhere());
            if (!$this->minIdFromDb) {
                // Если таблица ещё пуста, создадим пустую фиктивную запись в целях тестирования
                Db::query("INSERT INTO $dbtable (ID) VALUES (NULL)");
                $this->minIdFromDb = Db::getInsertId();
            }
        }
        return $this->minIdFromDb;
    }

    /**
     * В классе потомке можно переопределить, чтобы обозначит несколько тестовых объектов на которых проверять печатные формы
     */
    protected function getTestObjIdsForPrintForms()
    {
        return [$this->getTestObjId()];
    }

    protected function getModule(): string
    {
        static $module;
        if ($module === null) {
            $parts = explode('\\', get_class($this));
            $class = end($parts);
            $module = substr($class, 0, -4); //remove 'Cest' suffix
        }
        return $module;
    }

    protected function getModuleClassName(): string
    {
        $module = $this->getModule();
        return PROJECT_NAMESPACE . '\\Module\\' . $module . '\\' . $module;
    }

    protected function getModuleInstance(): ModuleAbstract
    {
        // Обязательно загрузим текущего пользователя вне контекста запроса
        //т.к. внутри конструкторов модулей или объектов
        // бывают проверки на права доступа текущего пользователя
        User::loadAsCurrent('admin');

        // Придётся создать инстанс приложения, чтобы ниже $moduleInstance сработал
        $appClass = PROJECT_NAMESPACE . '\\Application';
        new $appClass;

        $moduleClassName = $this->getModuleClassName();
        /** @var ModuleAbstract $moduleInstance */
        $moduleInstance = new $moduleClassName;

        return $moduleInstance;
    }

    public function getModuleClassReflection(): \ReflectionClass
    {
        $moduleClassName = $this->getModuleClassName();
        return new \ReflectionClass($moduleClassName);
    }

    protected function getModuleName()
    {
        return ModuleRegister::getModuleName($this->getModule());
    }

    /**
     * Отедльный вспомагательный метод, который проверяет наличия текста с названием модуля в шапке страницы
     * Этот метод можно переопределить в конкретныз Cest классах тестов, если проверяеть наличие названия модуля не нужно
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    protected function canSeeModuleNameInPageHead($I)
    {
        $I->canSeePageHead($this->getModuleName());
    }

    /**
     * Отедльный вспомагательный метод, который проверяет наличия текста с ID текущей записи в шапке страницы
     * Этот метод можно переопределить в конкретныз Cest классах тестов, если проверяеть наличие ID в шапке не нужно
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    protected function canSeeIdInPageHead($I)
    {
        $I->canSeePageHead('(ID: ' . $this->getTestObjId() . ')');
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeAddNew($I)
    {
        $I->amOnPage('?a=' . $this->getModule() . '&addnew=1');
        $this->canSeeModuleNameInPageHead($I);
        $I->canSeeElement('.btn-save');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeList($I)
    {
        $I->amOnPage('?a=' . $this->getModule());
        $this->canSeeModuleNameInPageHead($I);
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeListAjaxRefresh($I)
    {
        $I->sendAjaxPostRequest('?a=' . $this->getModule());
        $html = $I->grabResponse();

        $I->assertNotContainsXdebugError($html);
        $I->assertNotContainsSqlError($html);
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeView($I)
    {
        $I->amOnPage('?a=' . $this->getModule() . '&id=' . $this->getTestObjId() /*. '&XDEBUG_SESSION_START=PHPSTORM'*/);
        $this->canSeeModuleNameInPageHead($I);
        $this->canSeeIdInPageHead($I);
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeEdit($I)
    {
        $I->amOnPage('?a=' . $this->getModule() . '&editid=' . $this->getTestObjId());
        $this->canSeeModuleNameInPageHead($I);
        $this->canSeeIdInPageHead($I);
        $I->canSeeElement('.btn-save');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function submit($I)
    {
        $I->amOnPage('?a=' . $this->getModule() . '&editid=' . $this->getTestObjId());
        $I->submitForm('#edit-form', ['_continmethod' => 'view']);

        if ($I instanceof \AcceptanceTester) {

            $I->canSeeInCurrentUrl('?a=' . $this->getModule() . '&id=' . $this->getTestObjId());

        } elseif ($I instanceof \FunctionalTester) {

            $html = $I->grabResponse();
            $I->assertNotContainsXdebugError($html);
            $I->assertNotContainsSqlError($html);

        }
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeLog($I)
    {
        $testId = $this->getTestObjId();
        $I->wantTo("Log (ID: $testId)");
        $I->amOnPage('?a=' . $this->getModule() . '&logid=' . $testId);
        $this->canSeeModuleNameInPageHead($I);
        $this->canSeeIdInPageHead($I);
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function deleteDialog($I)
    {
        $testId = $this->getTestObjId();
        $I->wantTo("Delete dialog (ID: $testId)");
        $I->canSeeAjaxDialog('?a=' . $this->getModule() . '&delid=' . $testId);
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     * @param string $action
     */
    protected function testMassaction($I, string $action)
    {
        $I->expect('See Massaction Ajax Dialog');
        $url = "?a=" . $this->getModule() . "&p=massaction&action=$action";
        $I->sendAjaxPostRequest($url, ['CheckedIds' => $this->getTestObjId()]);
        $I->canSeeHttpHeader('x-light-showdialog');

        $jsonSchema = ['html' => 'string'];
        $I->seeResponseMatchesJsonType($jsonSchema);
    }

    /**
     * Проверяет все поля типа ajax, редактируются ли они через ajax?
     * @param \AcceptanceTester | \FunctionalTester $I
     * @dataProvider ajaxChangeFieldDataProvider
     */
    public function ajaxChangeField($I, \Codeception\Example $example)
    {
        if (!isset($example['field'])) {
            return;
        }
        /** @var FieldAbstract $field */
        $field = $example['field'];
        $fieldKey = $field->key;
        $testObjId = $this->getTestObjId();
        $I->wantTo("Change field via ajax");
        $module = $this->getModule();
        $url = "?a=$module&id=$testObjId&action=AjaxChangeField&fieldKey=$fieldKey";

        $oldValue = Db::getOne("SELECT {$field->key} FROM {$this->getDbTable()} WHERE ID = $testObjId");
        if ($field instanceof FieldDate) {

            $newValue = date('Y-m-d');

        } elseif ($field instanceof FieldCheckbox) {

            $newValue = !$oldValue;

        } elseif ($field instanceof FieldSelect) {

            $newValue = $oldValue;
            $options = $field->getEvaluatedOptions();
            foreach($options as $key => $option) {
                // Ищем какую-то поцию, которая не совпадает с $oldValue
                if ($key != $oldValue) {
                    $newValue = $key;
                    break;
                }
            }

        } else {

            $newValue = "NEW$oldValue";

        }

        $I->expect("Send post request to change field $fieldKey");
        $I->sendAjaxPostRequest($url, ['newValue' => $newValue]);
        $I->canSeeResponseCodeIs(200);

        $response = $I->grabResponse();
        $I->assertNotContainsSqlError($response);
        $I->assertNotContainsXdebugError($response);

        $actualText = strip_tags($response);

        $recordData = Db::getRow("SELECT * FROM {$this->getDbTable()} WHERE ID = $testObjId");
        $expectedHtml = $field->getHtml($newValue, $recordData);
        $expectedText = strip_tags($expectedHtml);
        $I->assertContains($expectedText, $actualText);

        // Возвращаем в базу предыдущее значение
        Db::query("UPDATE {$this->getDbTable()} SET {$field->key} = " . Db::escString($oldValue) . " WHERE ID = $testObjId");
    }

    protected function ajaxChangeFieldDataProvider()
    {
        $moduleInstance = $this->getModuleInstance();

        $moduleClassReflection = $this->getModuleClassReflection();
        $allowEditProp = $moduleClassReflection->getProperty('allowEdit');
        $allowEditProp->setAccessible(true);
        $allowEditValue = $allowEditProp->getValue($moduleInstance);

        if (!$allowEditValue) {
            return [['nofields' => 'allowEdit === false']];
        }

        $fieldsToBeTested = $moduleInstance->getFieldset()
            ->filterByMode(FieldAbstract::MODE_EDITSUBMIT)
            ->filterByParams(['ajax' => true]);

        $out = [];
        foreach ($fieldsToBeTested as $field) {
            $out[] = ['fieldKey' => $field->key, 'field' => $field];
        }

        if (!$out) {
            return [['nofields' => 'nofields']];
        }

        return $out;
    }

    /**
     * @group pdf
     *
     * @param \AcceptanceTester | \FunctionalTester $I
     * @param \Codeception\Example $example
     *
     * @dataProvider printFormsDataProvider
     */
    public function printForms($I, \Codeception\Example $example)
    {
        if (isset($example['noforms'])) {
            return;
        }

        $module = $this->getModule();
        $id = $this->getTestObjId();

        $printForm = $example['printForm'];
        $filename = $example['filename'];
        $out = $example['out'];
        $format = $example['format'];
        $hasFieldset = $example['hasFieldset'];

        $url = "?a=$module&id=$id&func=Print&form=$printForm";

        if ($hasFieldset) {

            // Эта печатная форма предполагает диалог для заполнения дополнительных данных.
            // Пока протестирует только появление самого диалогоа
            // TODO: надо бы ещё протестировать заполнение этого диалога и реальную печать
            $I->wantTo("See ajax dialog");
            $I->canSeeAjaxDialog($url);


        } else {

            if ($out & PrintForm::OUT_SAVE) {
                // Если эта форма предполагает сохранение в папку, то сначала очистим папку объекта
                $abs = FilesFolder::getAbsoluteModule($module, $id);
                File::fs()->remove($abs);
                File::fs()->mkdir($abs);
            }

            $I->wantTo("Print");
            $I->sendPOST($url);

            if ($out & PrintForm::OUT_STREAM) {

                // Если эта печатная форма подразумевает вывод в браузер пользователю, то проверим содержимое полученного файла

                $expectedContentType = '';
                switch ($format) {
                    case PrintForm::FORMAT_HTML:
                        $expectedContentType = 'text/html';
                        break;

                    case PrintForm::FORMAT_PDF:
                        $expectedContentType = 'application/pdf';
                        break;

                    case PrintForm::FORMAT_EXCEL:
                        $expectedContentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                        break;

                    case PrintForm::FORMAT_DOC:
                        $expectedContentType = 'text/html';
                        break;

                    case PrintForm::FORMAT_DOCX:
                        $expectedContentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                        break;
                }

                $I->expect("See Content-Type $expectedContentType");
                $I->canSeeHttpHeader('Content-Type', $expectedContentType);

                $responseBody = $I->grabResponse();
                $I->assertNotContainsSqlError($responseBody);
                $I->assertNotContainsXdebugError($responseBody);

                if ($format === PrintForm::FORMAT_EXCEL) {
                    $I->expect("Receive valid Excel file");
                    // Попробуем прочитать весь Excle файл, чтобы убедиться, что нет Exception
                    $tmpFile = LIGHT_DIR_ROOT . '/tests/_output/' . $printForm . '.xlsx';
                    file_put_contents($tmpFile, $responseBody);
                    $spreadsheet = IOFactory::load($tmpFile);
                    // Попробуем прочитать первый лист, чтобы убедиться, что нет Exception
                    $spreadsheet->getSheet(0);
                }

            }

            if ($out & PrintForm::OUT_SAVE) {

                // Если эта форма предполагает сохранение в папку, то проверим наличие файла в папке объекта
                $I->expect("Out file exists in files folder");
                $filepathAbs = FilesFolder::getAbsoluteModule($module, $id) . "/$filename";
                $I->assertFileExists($filepathAbs);

                if ($format === PrintForm::FORMAT_EXCEL) {
                    $I->expect("Valid Excel file in files folder");
                    // Попробуем прочитать весь Excle файл, чтобы убедиться, что нет Exception
                    $spreadsheet = IOFactory::load($filepathAbs);
                    // Попробуем прочитать первый лист, чтобы убедиться, что нет Exception
                    $spreadsheet->getSheet(0);
                }

            }
        }
    }

    protected function printFormsDataProvider()
    {
        $moduleInstance = $this->getModuleInstance();
        $moduleClassReflection = $this->getModuleClassReflection();
        $initPrintFormsMethod = $moduleClassReflection->getMethod('initPrintForms');
        $initPrintFormsMethod->setAccessible(true);
        $initPrintFormsMethod->invoke($moduleInstance);

        $printFormsProp = $moduleClassReflection->getProperty('printForms');
        $printFormsProp->setAccessible(true);
        $printForms = $printFormsProp->getValue($moduleInstance);

        $objProp = $moduleClassReflection->getProperty('_obj');
        $objProp->setAccessible(true);
        /** @var ObjAbstract $obj */
        $obj = $objProp->getValue($moduleInstance);
        $obj->setId($this->getTestObjId());
        $obj->loadData();

        $data = [];

        foreach ($printForms as $printForm => $printFormInstance) {

            /** @var PrintForm $printFormInstance */
            if (!$printFormInstance->testForObj($obj)) {
                // Эта форма не подходит к тетовому объекту, пропускаем
                continue;
            }

            $printFormClassReflection = new \ReflectionClass($printFormInstance);
            $getFilenameMethod = $printFormClassReflection->getMethod('getFilename');
            $getFilenameMethod->setAccessible(true);
            $filename = $getFilenameMethod->invokeArgs($printFormInstance, [$obj]);

            $outProp = $printFormClassReflection->getProperty('out');
            $outProp->setAccessible(true);
            $out = $outProp->getValue($printFormInstance);

            $outProp = $printFormClassReflection->getProperty('format');
            $outProp->setAccessible(true);
            $format = $outProp->getValue($printFormInstance);

            $data[] = [
                'printForm' => $printForm,
                'filename' => $filename,
                'out' => $out,
                'format' => $format,
                'hasFieldset' => $printFormInstance->hasFieldset(),
            ];
        }

        if (empty($data)) {
            $data[] = ['noforms' => 'noforms'];
        }

        return $data;
    }
}
