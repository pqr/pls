<?php
namespace Light\Traits;

use Light\Db;
use Light\Link;
use Light\ObjAbstract;
use Light\Response;

trait ModuleSessionTrait
{
    private $moduleSessionInited = false;

    /** @var int новое значение инициализируется в режиме List и потом используется в режимах View/Edit/Log для Next/Prev */
    private $moduleSessionId;

    private $moduleSessionStartPage;

    /** @var array массив ID объектов в текущей сессии */
    private $moduleSessionObjectsIds;

    private function lazyInitModuleSessionFromRequest()
    {
        if ($this->moduleSessionInited) {
            return;
        }

        if (isset($_GET['msid'])) {
            $res = Db::getRow("SELECT ID, IDs, StartPage FROM modulesession WHERE ID = @i", $_GET['msid']);
            if ($res) {
                $this->moduleSessionId = $res['ID'];
                $this->moduleSessionStartPage = (int)$res['StartPage'];
                $this->moduleSessionObjectsIds = explode(',', $res['IDs']);
            }
        }

        $this->moduleSessionInited = true;
    }

    /**
     * Возвращает параметры для url содержащие msid
     * @return array
     */
    protected function getModuleSessionUrlParams(): array
    {
        if (isset($_GET['msid']) || $this->moduleSessionId) {
            return ['msid' => $_GET['msid'] ?? $this->moduleSessionId];
        }

        return [];
    }

    private function createModuleSession(string $idsString, $tablePage)
    {
        $this->moduleSessionId = Db::insertRow(['IDs' => $idsString, 'StartPage' => (int)$tablePage], 'modulesession');
        $this->moduleSessionStartPage = (int)$tablePage;
        $this->moduleSessionObjectsIds = explode(',', $idsString);

        $this->moduleSessionInited = true;
    }

    /**
     * Устанавилвает номер страницы в зависимости от наличия moduleSession и findid
     * @param $findid
     * @return null
     */
    private function getTablePageFromModuleSession($findid)
    {
        $this->lazyInitModuleSessionFromRequest();

        //Определим, находится ли искомый findid в списке $this->_moduleSessionObjectsIds?
        if ($this->moduleSessionId
            && $findid
            && in_array($findid, $this->moduleSessionObjectsIds)
        ) {
            return $this->moduleSessionStartPage;
        }

        return null;
    }

    /**
     * @param ObjAbstract $obj
     * @param string $idUrlParam
     * @param array $additionalUrlParams дополнительные параметры для ссылок next/prev - нужно в Autolaunch log и Import log
     */
    final protected function addModuleSessionNextPrevLinks(ObjAbstract $obj, $idUrlParam, array $additionalUrlParams = [])
    {
        $this->lazyInitModuleSessionFromRequest();
        if ($this->moduleSessionId) {
            //Попробуем найти текущий объект в массиве ID текущей сесии
            $currentIndex = array_search($obj->getId(), $this->moduleSessionObjectsIds);
            if ($currentIndex !== false) {
                //текущий объхект нашелся в массиве $this->_moduleSessionObjectsIds, попробуем найти следующий и предыдущий
                $linksHtml = '';
                $linkParams = $this->getModuleSessionNextPrevLink(
                    $currentIndex - 1,
                    $obj,
                    $idUrlParam,
                    $additionalUrlParams
                );
                if ($linkParams) {
                    $linksHtml .= '<a class="prev-link waves-effect waves-light btn-floating btn-small" title="' .
                        htmlspecialchars($linkParams['title']) .
                        ' (← Ctrl)" href="' . $linkParams['url'] . '">←</a>';
                }
                $linkParams = $this->getModuleSessionNextPrevLink(
                    $currentIndex + 1,
                    $obj,
                    $idUrlParam,
                    $additionalUrlParams
                );
                if ($linkParams) {
                    $linksHtml .= '<a class="next-link waves-effect waves-light btn-floating btn-small" title="' .
                        htmlspecialchars($linkParams['title']) .
                        ' (Ctrl →)" href="' . $linkParams['url'] . '">→</a>';
                }

                Response::setPageHeadRightHtml($linksHtml);
            }
        }
    }

    /**
     * @param int $index текущая позиция в массиве $this->_moduleSessionObjectsIds
     * @param ObjAbstract $obj текущий обхъект, который будет склонирован, получен следующий и предыдущий, чтобы вывести описание в title
     * @param string $idUrlParam имя url параметра в который нужно положить ID следущего и предыдущего объекта
     * @param array $additionalUrlParams
     * @return string
     */
    private function getModuleSessionNextPrevLink($index, ObjAbstract $obj, $idUrlParam, array $additionalUrlParams)
    {
        $newObj = null;

        if (isset($this->moduleSessionObjectsIds[$index])) {

            $objId = $this->moduleSessionObjectsIds[$index];
            //Попробуем загрузить объект
            $newObj = clone $obj;
            $newObj->setId($objId);
            if (!$newObj->loadDataForDescription()) {
                //Загурзить не удалось, обнуляем переменную объекта
                $newObj = null;
            }
        }

        $out = false;
        if ($newObj) {
            $title = $newObj->getDescriptionHtml();
            $urlParams = [$idUrlParam => $newObj->getId()] + $additionalUrlParams + $this->getModuleSessionUrlParams();
            $out = ['url' => Link::url($urlParams), 'title' => $title];
        }

        return $out;
    }

    /**
     * Переопределяем один из основных методов класса Table
     * Если в дочернем классе использующем этот Trait этот мето будет переопределён ещё один раз, нужно не забыть
     * вызвать $this->getModuleSessionUrlParams()
     *
     * @return array
     */
    protected function getTableLinkUrlParams(): array
    {
        return $this->getModuleSessionUrlParams();
    }
}
