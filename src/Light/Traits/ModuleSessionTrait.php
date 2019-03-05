<?php

namespace Light\Traits;

use Light\Db;
use Light\Link;
use Light\ObjAbstract;
use Light\Request;
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

                $this->tableLinkUrlParams['msid'] = $this->moduleSessionId;
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

    private function createModuleSession(array $ids, $tablePage)
    {
        $this->moduleSessionId = Db::insertRow(['IDs' => implode(',', $ids), 'StartPage' => (int)$tablePage], 'modulesession');
        $this->moduleSessionStartPage = (int)$tablePage;
        $this->moduleSessionObjectsIds = $ids;

        $this->moduleSessionInited = true;

        $this->tableLinkUrlParams['msid'] = $this->moduleSessionId;
    }

    /**
     * Устанавилвает номер страницы в зависимости от наличия moduleSession и findid
     * @param $findid
     * @return null
     */
    protected function getTablePageFromModuleSession($findid)
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
     * @param int $currentId
     * @param string $idUrlParam
     * @param array $additionalUrlParams дополнительные параметры для ссылок next/prev - нужно в Autolaunch log и Import log
     */
    final protected function addModuleSessionNextPrevLinks(
        int $currentId,
        $idUrlParam = 'id',
        array $additionalUrlParams = []
    ) {
        $this->lazyInitModuleSessionFromRequest();
        if ($this->moduleSessionId) {
            //Попробуем найти текущий объект в массиве ID текущей сесии
            $currentIndex = array_search($currentId, $this->moduleSessionObjectsIds);
            if ($currentIndex !== false) {
                //текущий объхект нашелся в массиве $this->_moduleSessionObjectsIds, попробуем найти следующий и предыдущий
                $linksHtml = '';
                $prevUrl = $this->getModuleSessionNextPrevLink(
                    $currentIndex - 1,
                    $idUrlParam,
                    $additionalUrlParams
                );
                if ($prevUrl) {
                    $linksHtml .= '<a class="prev-link waves-effect btn-floating btn-small" title="← Ctrl" href="' . $prevUrl . '">←</a>';
                }
                $nextUrl = $this->getModuleSessionNextPrevLink(
                    $currentIndex + 1,
                    $idUrlParam,
                    $additionalUrlParams
                );
                if ($nextUrl) {
                    $linksHtml .= '<a class="next-link waves-effect btn-floating btn-small" title="Ctrl →" href="' . $nextUrl . '">→</a>';
                }

                Response::setPageHeadRightHtml($linksHtml);
            }
        }
    }

    /**
     * @param int $index текущая позиция в массиве $this->_moduleSessionObjectsIds
     * @param string $idUrlParam имя url параметра в который нужно положить ID следущего и предыдущего объекта
     * @param array $additionalUrlParams
     * @return string
     */
    private function getModuleSessionNextPrevLink(
        $index,
        string $idUrlParam,
        array $additionalUrlParams
    ): string {
        if (isset($this->moduleSessionObjectsIds[$index])) {

            $id = $this->moduleSessionObjectsIds[$index];
            return Link::create(Request::getModule())
                ->params([$idUrlParam => $id] + $additionalUrlParams + $this->getModuleSessionUrlParams())
                ->getHref();
        }

        return '';
    }

}
