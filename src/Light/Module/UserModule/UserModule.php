<?php
namespace Light\Module\UserModule;

use Carbon\Carbon;
use Light\A;
use Light\ApplicationAbstract;
use Light\Css;
use Light\Db;
use Light\HomeBase\HomeBlocksPage;
use Light\Js;
use Light\ModuleRegister;
use Light\Request;
use Light\Response;
use Light\User;

class UserModule
{
    public function __invoke()
    {
        if (!Request::isAjax()) {

            $this->printInitialHtml();

        }
    }

    private function printInitialHtml()
    {
        //Js::addReactJs();
        Js::$preact = true;
        Js::$preactcompat = true;
        Js::$mobx = true;
        Js::addLight('build/UserModuleBuild.js');

        $orderBy = User::getParam('UserModuleUsersOrderBy') ?? 'Name ASC';
        list($usersSortFieldKey, $usersSortDirection) = explode(' ', $orderBy);

        Response::addJsData([

            'groups' => $this->getGroups(),
            'branchoffices' => $this->getBranchOffices(),
            'users' => $this->getUsers(),
            'usersSortFieldKey' => $usersSortFieldKey,
            'usersSortDirection' => $usersSortDirection,
            'modulesPerSection' => $this->getModulesPerSectionForJs(),
            'userRoles' => ApplicationAbstract::$app->getUserRolesList()

        ]);

        print '<div id="app"></div>';
    }

    private function getModulesPerSectionForJs()
    {
        $modules = [];
        foreach (ModuleRegister::getModulesKeys() as $moduleKey) {
            $modules[] = [
                'key' => $moduleKey,
                'name' => ModuleRegister::getModuleName($moduleKey),
                'section' => ModuleRegister::getModuleSection($moduleKey),
                'specialAccessList' => $this->getModuleSpeciallAccessListForJs($moduleKey)
            ];
        }

        $perSection = A::groupBy($modules, 'section');

        // Сделаем из ассоциативного массива обычный
        $out = [];
        foreach ($perSection as $section => $modules) {
            $out[] = [
                'section' => $section,
                'modules' => $modules
            ];
        }

        return $out;
    }

    private function getModuleSpeciallAccessListForJs(string $moduleKey)
    {
        $out = [];
        foreach (ApplicationAbstract::$app->getSpecialAccessList() as $specialAccessInfo) {

            if ($specialAccessInfo[0] === $moduleKey) {
                $out[] = [
                    'accessKey' => $specialAccessInfo[1],
                    'description' => $specialAccessInfo[2]
                ];
            }
        }

        if ($moduleKey === 'Logviewer') {
            $out[] = [
                'accessKey' => 'Logviewer1',
                'description' => 'View logs in tab'
            ];
        }

        return $out;
    }

    private function getGroups()
    {
        $groups = Db::getAll(
            "SELECT ID, Name, OrderID, ModuleAccessJSON, SpecialAccessJSON
             FROM usergroup 
             ORDER BY OrderID"
        );

        foreach ($groups as &$group) {

            $group['moduleAccess'] = json_decode($group['ModuleAccessJSON'], true);
            $group['specialAccess'] = json_decode($group['SpecialAccessJSON'], true);

            unset($group['ModuleAccessJSON'], $group['SpecialAccessJSON']);

        }

        return $groups;
    }

    private function getBranchOffices()
    {
        return Db::getAll("SELECT ID, Name FROM userbranchoffice ORDER BY ID");
    }

    private function getUserRolesForJs($userResponsibleRoles)
    {
        $out = [];
        foreach (ApplicationAbstract::$app->getUserRolesList() as $roleKey => $_) {
            $out[$roleKey] = strpos($userResponsibleRoles, '|' . $roleKey . '|') !== false;
        }
        return $out;
    }

    private function getUsers()
    {
        $users = Db::getAll(
            "SELECT ID, Name, Login, Email, InnerPhone, BirthDay, BirthMonth, Started, BranchOfficeID, Position, GroupID, `Password`, Active, ResponsibleRoles, ModuleAccessJSON, SpecialAccessJSON
                , (SELECT LastRefreshDateTime FROM user_visit WHERE UserID = user.ID) AS LastRefreshDateTime
             FROM user"
        );

        foreach ($users as &$user) {

            if ($user['LastRefreshDateTime']) {
                $user['Idle'] = (new Carbon($user['LastRefreshDateTime']))->diffForHumans();
                $user['IdleTimestamp'] = strtotime($user['LastRefreshDateTime']);
            } else {
                $user['IdleTimestamp'] = 0;
            }

            $user['ResponsibleRoles'] = $this->getUserRolesForJs($user['ResponsibleRoles']);

            $user['moduleAccess'] = json_decode($user['ModuleAccessJSON'], true);
            $user['specialAccess'] = json_decode($user['SpecialAccessJSON'], true);

            unset($user['ModuleAccessJSON'], $user['SpecialAccessJSON']);

        }

        return $users;
    }

    /**
     * Этот метод используется для сохранения пользователей и для сохранения групп одновременнов
     * Внутри определяем тип сохраняемого объекта по наличию в запросе параметра $_POST['Login']
     */
    public function proceedSubmit()
    {
        $type = isset($_POST['Login']) ? 'user' : 'group';

        if ($type === 'user') {
            $obj = new UserModuleUserObj();
        } else {
            $obj = new UserModuleGroupObj();
        }

        $dataFromPost = $obj->getDataFromRequest($_POST);
        $dataFromPost['ModuleAccessJSON'] = json_encode(array_map('intval', $_POST['moduleAccess']), JSON_PRETTY_PRINT);
        $dataFromPost['SpecialAccessJSON'] = json_encode(array_map('intval', $_POST['specialAccess']),
            JSON_PRETTY_PRINT);

        if ($type === 'user') {
            // Отедльно обработаем чекбоксы с выбором ролей пользователя - они приходят в виде ассоциативного массива
            // ключи массива - это названия ролей, значения - true/false
            $checkedRolesFromPost = [];
            if (isset($_POST['ResponsibleRoles']) && is_array($_POST['ResponsibleRoles'])) {
                foreach ($_POST['ResponsibleRoles'] as $roleKey => $roleChecked) {
                    if ($roleChecked === 'true') {
                        $checkedRolesFromPost[] = $roleKey;
                    }
                }
            }
            if ($checkedRolesFromPost) {
                $dataFromPost['ResponsibleRoles'] = '|' . implode('|', $checkedRolesFromPost) . '|';
            } else {
                $dataFromPost['ResponsibleRoles'] = '';
            }
        }

        $id = (int)$_POST['ID'];
        if ($type === 'user') {
            //Проверим на уникальность логина
            $existId = Db::getOne("SELECT ID FROM user WHERE Login = @s AND ID != @i", $dataFromPost['Login'], $id);
            if ($existId) {
                Response::sendToast('error',
                    'User with login <strong>' . htmlspecialchars($dataFromPost['Login']) . '</strong> already exist');
                Response::abort(401);
            }
        }

        // Если был передан какой-то ID, проверим что такой пользователь существует и обновим его
        if ($id && $obj->setId($id) && $obj->loadData()) {
            $obj->updateDb($dataFromPost);
        } else {
            $obj->insertDb($dataFromPost);
        }

        // Возвращаем обновлённые данные как они сохранились в базу, чтобы освежить данные на клиенте
        $newData = $obj->getData();

        if ($type === 'user') {
            $newData['ResponsibleRoles'] = $this->getUserRolesForJs($newData['ResponsibleRoles']);
        }

        $newData['moduleAccess'] = json_decode($obj['ModuleAccessJSON'], true);
        $newData['specialAccess'] = json_decode($obj['SpecialAccessJSON'], true);
        unset($newData['ModuleAccessJSON'], $newData['SpecialAccessJSON']);

        if ($type === 'user') {
            $lastRefreshDateTime = Db::getOne("SELECT LastRefreshDateTime FROM user_visit WHERE UserID = @i", $id);
            if ($lastRefreshDateTime) {
                $newData['Idle'] = (new Carbon($lastRefreshDateTime))->diffForHumans();
                $newData['IdleTimestamp'] = strtotime($lastRefreshDateTime);
            } else {
                $newData['IdleTimestamp'] = 0;
            }
        }

        Response::json($newData);

    }

    public function proceedLoginAs()
    {
        session_regenerate_id(true);
        $_SESSION['lightauth'] = Db::getOne("SELECT Login FROM user WHERE ID = " . (int)$_GET['userId']);
        Response::redirect(ModuleRegister::getDefaultModule());
    }

    public function proceedViewHome()
    {
        $userId = (int)$_GET['userId'];

        if (isset($_GET['class'])) {

            if (Request::isXls()) {
                //Запрос отдельно взятого блока Home - это может быть только отправка в Excel!
                HomeBlocksPage::sendExcel($userId, $_GET['class']);
            } else {
                //Запрос на обновление таблицы через ajax
                HomeBlocksPage::tableAjaxReload($userId, $_GET['class']);
            }

        } else {

            //Наконец, это обычный режим view
            //Проверим, возможно в самом проекте в папке Home хранится какой-то проект-специфичный Home.css?
            $fileHomeCss = LIGHT_DIR_PUBLIC . '/css/Home.css';
            if (file_exists($fileHomeCss)) {
                Css::add('Home.css');
            }

            $userName = Db::getOne("SELECT Name FROM user WHERE ID = @i", $userId);
            Response::setPageTitle($userName . ' - Home');
            Response::setPageHeadHtml(htmlspecialchars($userName) . ' - Home');
            Response::disableMainmenu();
            HomeBlocksPage::printHomeBlocks($userId);

        }
    }
}
