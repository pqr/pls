<?php


namespace Light\ModuleBase;

use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Link;
use Light\Metric;
use Light\Misc;
use Light\ObjAbstract;
use function Light\reportException;
use Light\Request;
use Light\Response;
use Light\Session;
use Light\SubtableBase\Subtable;
use Light\Timer;
use Light\User;

/**
 * Этот trait безусловно подключается к ModuleAbstract.
 * Код вынесен в этот trait исключительно чтобы облегчить файл ModuleAbstract.php
 */
trait DeleteTrait
{
    /**
     * Реальное удаление объекта из базы
     * Если реальное удаление невозможно, т.к. пользователь не выбрал какие-то поля на форме подтверждения
     * то следует вернуть http ответ 400 c текстом сообщения, которое будет выведено в alert()
     */
    protected function proceedDelete()
    {
        $this->_state = self::STATE_DELETE;

        $id = (int)$_GET['delid'];

        // Попробоуем загрузить объект и одновременно проверим права доступа к нему, если что-то не так - будет редирект
        $this->loadObjAndCheck($id);

        if (!$this->allowDelete || !User::hasDeleteAccess() || !$this->hasDeleteAccessRightToObj($this->_obj)) {
            $msg = Config::$lang === 'ru' ? 'Нет доступа на удаление' : 'No access to delete';
            Response::abort(403, $msg);
            return;
        }

        if (!Request::isPost()) {
            // Если пришел не POST запрос, значит это запрос на рендеринг диалогового окна с подтверждением удаления
            $this->sendDeleteConfirmDialog();
            return;
        }

        try {

            Db::begin();

            // Ставим эксклюзивную блокировку на текущую запись
            // https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html
            Db::query("SELECT 1 FROM {$this->getDbTable()} WHERE ID = @i FOR UPDATE", $this->_obj->getId());

            // Запрос пришел из диалогового окна, нужно проверить, приавильно ли было заполнено диалоговое окно?
            // Если внутри checkDeleteConfirmResult выяснится, что пользователь что-то не заполнил - этот метод
            // сам отправит пользователю новый диалог или другое сообщение
            if (!$this->checkDeleteConfirmResult()) {
                Db::commit();
                return;
            }

            // Непосредственное удаление
            $timer = new Timer();

            $oldData = $this->getOldDataBeforeSubmitOrDelete($this->_obj);

            $this->beforeDelete($this->_obj);
            $this->proceedDeleteSubtables($this->_obj);
            $this->proceedDeleteObj($this->_obj);
            $this->_obj->afterDelete($oldData, $this->flash);
            $this->afterDelete($this->_obj, $oldData);

            Db::commit();

            $msg = "<strong>$this->_obj</strong> - " . (Config::$lang === 'ru' ? 'удалено' : 'deleted');
            $this->flash->addSuccess($msg);

            Metric::newrelicMetric($this->getModule() . '/delete', $timer->getMs());

        } catch (\Throwable $e) {

            Db::rollback();
            $this->flash->addError($e->getMessage());
            reportException($e);

        }

        if (isset($_GET['fromList'])) {

            // Возвращаем специальный header, который обновит таблицу в режиме List с помощью js на стороне клиента
            Response::sendHeaderJsReloadTable($this->getModule());
            ApplicationAbstract::terminate();

        }

        // Если же удалени было не из режима List, то делаем редирект на режим List
        $this->redirectToList();

    }

    /**
     * Метод отправляет диалог на подтверждение удаления.
     * Этот метод можно переопределить в классе потомке, если нужен какой-то особый диалог
     */
    protected function sendDeleteConfirmDialog()
    {
        $module = $this->getModule();
        $id = $this->_obj->getId();

        $formAction = Link::create($module)
            ->ajax()
            ->params(['delid' => $id]);
        if (isset($_GET['fromList'])) {
            $formAction->params(['fromList' => 1]);
        }

        $select2AjaxUrl = Link::create($module)
            ->params(['fromDeleteDialogId' => $id]);

        $bodyHtml = '<form action="' . $formAction->getHref() . '#ajax"
                           data-select2ajaxurl="' . $select2AjaxUrl->getHref() . '" 
                           method="post">'
            . $this->getDeleteConfirmationDialogHtml()
            . '</form>';

        $deleteText = Config::$lang === 'ru' ? 'Удалить' : 'Delete';
        $cancelText = Config::$lang === 'ru' ? 'Отмена' : 'Cancel';
        Response::showDialog($bodyHtml, $deleteText, null, $cancelText);
    }

    /**
     * Этот метод удобно переопределять в классах потоках, если нужны дополнительные дейтсвия по удалению чего-либо
     * @param ObjAbstract $obj
     * @param string $dbLogMsg
     */
    protected function proceedDeleteObj(ObjAbstract $obj, $dbLogMsg = '')
    {
        if ($this->folders) {
            $this->deleteFolder($obj->getData());
        }
        $obj->deleteDb($dbLogMsg);
    }

    /**
     * @param ObjAbstract $obj
     */
    private function proceedDeleteSubtables(ObjAbstract $obj)
    {
        foreach ($this->subtables as $subtableClass => ['readonly' => $readonly]) {
            if (!$readonly) {
                /** @var Subtable $subtable */
                $subtable = $this->getSubtable($subtableClass);
                $subtable->loadData();
                $subtable->deleteAll();
            }
        }
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Метод для переопределения в классах потомках - должен вернуть ассоциативный массив из
     * 'имя класса объекта' => 'текст для вывода в диалог'
     *
     * Например, так:
     * return [
     *     ShipmentObj::class => 'Shipments'
     * ]
     *
     * @return array
     */
    protected function whileDeleteGetLinkedClasses(): array
    {
        return [];
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Возвращает имя поля (внешний ключ) в других таблицах, которые ссылаются на удаляемую запись.
     * Например, если мы удаляем клиента (Customer), то этот метод вернёт 'CustomerID'
     *
     * @param string $className для какого внешнего класса пытаемся сформировать внешний ключ?
     * @return string
     */
    protected function whileDeleteGetForeignFieldKey(string $className): string
    {
        return $this->getModule() . 'ID';
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Метод возвращает массив ID записей указанного класса связанных с удаляемоей записью.
     * Например, если удаляем клеиента, то этот метод будет последовательно вызыван с именами классов
     * ShipmentObj::class, CInvoice::class, CPayment::class и т.п.
     * @param string $className
     * @param int $deletingId
     * @return array
     */
    protected function whileDeleteGetLinkedIds(string $className, int $deletingId): array
    {
        /** @var ObjAbstract $obj */
        //Создадим объект требуемого класса, чтобы узнать таблицу в базе к которой он привязан
        $obj = new $className;
        $dbTable = $obj->getDbTable();
        return Db::getCol(
            "SELECT ID
             FROM $dbTable
             WHERE {$this->whileDeleteGetForeignFieldKey($className)} = @i"
            , $deletingId
        );
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Возвращает HTML строку с указанием числа связанных объектов каком-то другом модуле.
     * В этом сообщении автоматически будет указана ссылка на внешний модуль (на режим list с фильтрами)
     *
     * @param string $className
     * @param int $oldId
     * @param string $description
     * @param int $countLinkedIds
     * @return string
     */
    protected function whileDeleteGetHtmlAboutLinkedRecords(
        $className,
        $oldId,
        $description,
        $countLinkedIds
    ) {
        $outHtml = $description . ': <strong>' . $countLinkedIds . '</strong> ';
        $link = $this->whileDeleteGetLinkViewMore($className, $oldId);
        if ($link) {
            $outHtml .= ' <small>' . $link . '</small>';
        }


        return $outHtml;
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Возвращает HTML строку со ссылкой на просмотр связанных записей в соотвествующем модуле
     * Этот метод можно переопределить в классах потомках, если для формирования ссылки нужно указать какие-то
     * хитрые фильтры
     *
     * @param string $className
     * @param int $deletingId
     * @return string|bool
     */
    protected function whileDeleteGetLinkViewMore(string $className, int $deletingId): string
    {
        $externalModule = Misc::getModule($className);
        if (User::hasReadAccess($externalModule)) {
            $msg = Config::$lang === 'ru' ? 'посмотреть...' : 'view...';
            return Link::create($externalModule, $msg)
                ->filters($this->whileDeleteGetLinkViewMoreFilters($className, $deletingId))
                ->blank()
                ->getHtml();
        }

        return '';
    }

    protected function whileDeleteGetLinkViewMoreFilters(string $className, int $deletingId): array
    {
        return [$this->whileDeleteGetForeignFieldKey($className) => $deletingId];
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Возвращает HTML код для вывода элемента выбора новой записи вместо удаляемой. Обычно это выпадающий список
     *
     * @param int $deletingId
     * @return F
     */
    protected function whileDeleteFieldForReplacement(int $deletingId): F
    {
        $options = "SELECT ID, Name FROM {$this->getDbTable()} WHERE ID != $deletingId ORDER BY Name";
        return F::createSelect('NewValueForReplacement', [
            'options' => $options,
        ]);
    }

    private $_alertDeleteButton = [];

    protected function setAlertDeleteButton(
        ?string $text,
        string $icon = 'fa-exclamation-triangle',
        string $color = '#b35900'
    ) {
        if ($text) {
            $this->_alertDeleteButton = [$text, $icon, $color];
        } else {
            $this->_alertDeleteButton = [];
        }
    }

    protected function getDeleteConfirmationDialogHtml()
    {
        $deletingId = $this->_obj->getId();
        //Выведем диалоговое окно на подтверждение удаления отправителя
        $linkedMessagess = [];
        foreach ($this->whileDeleteGetLinkedClasses() as $className => $description) {
            $linkedIds = $this->whileDeleteGetLinkedIds($className, $deletingId);
            $countLinkedIds = count($linkedIds);
            if ($countLinkedIds > 0) {
                $linkedMessagess[] = $this->whileDeleteGetHtmlAboutLinkedRecords($className, $deletingId,
                    $description, $countLinkedIds);
            }
        }

        if ($linkedMessagess) {
            $textLinked = Config::$lang === 'ru'
                ? 'Удаляемая запись связана с другими записями в базе'
                : 'You are triing to delete a record which is linked to anothed records in database';
            $outHtml = "$textLinked:<br>";
            $outHtml .= '<ul><li>' . implode('</li><li>', $linkedMessagess) . '</li></ul>';

            //Далее нужно вывести выпадающий список для выбора элемента на замену
            $textChoose = Config::$lang === 'ru'
                ? 'Выберите новый элемен на замену удаляемому'
                : 'Choose another record to replace deleted';
            $outHtml .= "$textChoose:<br>";
            $outHtml .= $this->whileDeleteFieldForReplacement($deletingId)->getHtmlInput('');
            return $outHtml;
        }

        $alertDeleteButton = '';
        if ($this->_alertDeleteButton) {
            [$text, $icon, $color] = $this->_alertDeleteButton;
            $alertDeleteButton = '<div class="alert-delete-button" style="color: ' . $color . ';"><i class="fa ' . $icon . '"></i> ' . $text . '</div>';
        }

        $textDelete = Config::$lang === 'ru' ? 'Удалить' : 'Delete';

        return $alertDeleteButton . '<div>' . $textDelete . ' <strong>' . $this->_obj->getDescriptionHtml() . '</strong>?</div>';
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Метод для переопределения в классах потоках - здесь можно проверить какие-то поля пришедшие с формы подтверждения
     * и вернуть новый диалог, если что-то не устраивает
     */
    protected function checkDeleteConfirmResult(): bool
    {
        $linkedClasses = $this->whileDeleteGetLinkedClasses();
        if (!$linkedClasses) {
            return true;
        }

        //Сразу определим вспомагательные значения
        $textDeleting = Config::$lang === 'ru' ? 'Удаление' : 'Deleting';
        $dbLogMsg = $textDeleting . ' ' . $this->_obj->getDescriptionHtml();
        $deletingId = $this->_obj->getId();

        $messagess = [];

        // Проверим, а был ли передан ID нового элемента на замену?
        $newId = (int)$this->whileDeleteFieldForReplacement($deletingId)->getDataFromRequest($_POST);

        // Для начала проверим, есть ли связанные записи?
        foreach ($linkedClasses as $className => $description) {
            $linkedIds = $this->whileDeleteGetLinkedIds($className, $deletingId);
            $countLinkedIds = count($linkedIds);
            if ($countLinkedIds > 0) {
                // Нашли какие-то связанные записи!
                if (!$newId) {
                    //Сразу возвращаем диалоговое окно с ошибкой
                    Response::showDialog('<span class="bad">При удалении нужно выбрать элемент на замену удаляемому</span>');
                    return false;
                }

                //Производим непосредственное обновление связанных записей в базе!
                $updated = 0;
                foreach ($linkedIds as $linkedId) {
                    $affectedRows = $this->whileDeleteUpdateLinkedRecord($className, $linkedId, $newId, $dbLogMsg);
                    if ($affectedRows) {
                        $updated++;
                    }
                }
                if ($updated) {
                    //Если что-то было обновлено (а должно было, ибо $linkedIds не пустой массив), то выведем
                    //сообщение с количеством обновлённых
                    $messagess[] = $description . ': <strong>' . $updated . '</strong>';
                }
            }
        }

        if ($messagess) {
            //В процессе удаления были произведены какие-то обновления в других таблицах, выведем сообщение об этом
            $textUpdated = Config::$lang === 'ru' ? 'Обновлено' : 'Updated';
            $msg = "$textUpdated:<br>";
            $msg .= '<ul><li>' . implode('</li><li>', $messagess) . '</li></ul>';
            Session::flashSuccess($msg);
        }

        return true;
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Обновляет одну указанную запись в связанной таблице. Метод может быть переопределён, если нужно как-то хитро
     * обновлять
     *
     * @param string $className
     * @param int $linkedId
     * @param int $newId
     * @param string $dbLogMsg
     * @return int affectedRows
     * @throws \Exception
     */
    protected function whileDeleteUpdateLinkedRecord(
        string $className,
        int $linkedId,
        int $newId,
        string $dbLogMsg
    ): int {
        /** @var ObjAbstract $obj */
        $obj = new $className;
        $obj->setId($linkedId);
        $updateData = [$this->whileDeleteGetForeignFieldKey($className) => $newId];
        // Возвращаем affected rows
        return $obj->updateDb($updateData, $dbLogMsg);
    }

    protected function beforeDelete(ObjAbstract $obj)
    {
    }

    /**
     * Метод для переопределения в классах потомках
     * Вызывается после удаления текущего объекта и всех подтаблиц
     * Данные текущего объекта при этом продолжают быть доступными в $this->_obj,
     * В дополнение к этому метод получает в качестве единственного параметра массив $oldData - это результат работы
     * метода $this->getOldDateBeforeSubmitOrDelete(), который можно переопределить в классах потмоках, чтобы насытить его
     * дополнительными данными (например, данными подтаблиц)
     * @param ObjAbstract $obj
     * @param array $oldData
     */
    protected function afterDelete(ObjAbstract $obj, array $oldData)
    {
    }
}
