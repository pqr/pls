<?php

namespace Light\Autolaunch;

use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Module\Autolaunch\AutolaunchAbstract;

class UpdateUserFilterSettings extends AutolaunchAbstract
{
    protected static $_title = 'Обновляет пользовательские настройки фильтров';
    protected static $_description = 'Необходимо запускать при добавлении/удалении фильтров';

    const TYPE_ADD = 1;
    const TYPE_REMOVE = 2;

    public function initFieldset()
    {
        $this->addFields(
            F::createSelect('TableId', [
                'name' => 'Table ID',
                'empty' => false,
                'options' => "SELECT ID, CONCAT(ID, ': ', TableName) FROM dblog_table"
            ]),
            F::createSelect('Type', [
                'name' => 'Действие',
                'empty' => false,
                'options' => [
                    self::TYPE_ADD => 'Добавление',
                    self::TYPE_REMOVE => 'Удаление'
                ]
            ]),
            F::createInt('Number', ['name' => 'Номер'])
        );
    }

    protected function run(array $params)
    {

        if ($params['TableId'] && $params['Type'] && $params['Number']) {

            $count = 0;

            $data = Db::getAll("SELECT * FROM user_filter_settings WHERE Sort != '' AND Invisible != '' AND TableID = @i", $params['TableId']);

            foreach ($data as $userData) {

                $sort = json_decode($userData['Sort'], true);
                $invisible = json_decode($userData['Invisible'], true);

                if ($params['Type'] == self::TYPE_ADD) {

                    // обновим сортировку
                    foreach ($sort as &$table) {
                        foreach ($table as &$item) {
                            if ($item >= $params['Number']) {
                                $item = (string)($item + 1);
                            }
                        }
                    }
                    // добавил новый фильтр в начало первой таблицы
                    array_unshift($sort[0], (string)$params['Number']);

                    // обновим видимость
                    $newInvisible = [];
                    foreach ($invisible as $invisibleKey => $invisibleItem) {
                        if ($invisibleKey >= $params['Number']) {
                            $newInvisible[$invisibleKey + 1] = (string)$invisibleItem;
                        } else {
                            $newInvisible[$invisibleKey] = (string)$invisibleItem;
                        }
                    }
                    // сделаем новый фильтр видимым
                    $newInvisible[$params['Number']] = 0;
                    $invisible = $newInvisible;

                } elseif ($params['Type'] == self::TYPE_REMOVE) {

                    // обновим сортировку
                    foreach ($sort as &$table) {
                        if ($key = array_search($params['Number'], $table)) {
                            unset($table[$key]);
                        }
                        foreach ($table as &$item) {
                            if ($item > $params['Number']) {
                                $item = (string)($item - 1);
                            }
                        }
                        $table = array_values($table);
                    }

                    // обновим видимость
                    unset($invisible[$params['Number']]);
                    $newInvisible = [];
                    foreach ($invisible as $invisibleKey => $invisibleItem) {
                        if ($invisibleKey > $params['Number']) {
                            $newInvisible[$invisibleKey - 1] = (string)$invisibleItem;
                        } else {
                            $newInvisible[$invisibleKey] = (string)$invisibleItem;
                        }
                    }
                    $invisible = $newInvisible;

                }

                $sort = json_encode($sort);
                $invisible = json_encode($invisible);

                Db::replaceRow([
                    'CreatedBy' => $userData['CreatedBy'],
                    'TableID' => $params['TableId'],
                    'Sort' => $sort,
                    'Invisible' => $invisible,
                ], 'user_filter_settings');

                $count++;

            }

            $tableName = Db::getOne("SELECT CONCAT(TableName, ' (ID: ', ID, ')') FROM dblog_table WHERE ID = @i", $params['TableId']);
            $typeName = $params['Type'] == self::TYPE_ADD ? 'Добавление' : 'Удаление';
            $numberName = $params['Number'];

            $msg = "Таблица: " . $tableName . ", действие: " . $typeName . ", номер: " . $numberName;
            $this->_msgBuff->addInfo($msg);
            $this->_msgBuffShort->addInfo($msg);

            $msg = "Обновленно пользовательских настроек: " . $count;
            $this->_msgBuff->addSuccessOrInfo($msg, $count);
            $this->_msgBuffShort->addSuccessOrInfo($msg, $count);

        } else {

            $msg = "Не входных данных";
            $this->_msgBuff->addWarning($msg);
            $this->_msgBuffShort->addWarning($msg);

        }

    }
}