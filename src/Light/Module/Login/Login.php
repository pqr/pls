<?php
namespace Light\Module\Login;

use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\ModuleRegister;
use Light\Request;
use Light\Response;
use Light\Template;
use Light\User;

class Login
{
    public function __invoke()
    {
        if (Request::getMehod() === 'POST') {

            sleep(0.5);
            $this->loginViaPost();

        } else {

            $this->renderLoginPage();

        }
    }

    protected function loginViaPost()
    {
        if (!isset($_POST['username'])
            || empty($_POST['username'])
            || trim($_POST['username']) === '') {
            $this->renderLoginPage('Empty Login', 'username');
            return;
        }

        if (!isset($_POST['password'])
            || trim($_POST['password']) === '') {
            $this->renderLoginPage('Empty Password', 'password');
            return;
        }

        $login = trim($_POST['username']);

        // Проверим, существует ли такой пользователь в базе?
        $user = Db::getRow("SELECT ID, Active, Password FROM `user` WHERE Login = @s", $login);
        if (!$user) {
            $this->renderLoginPage("User $login not found", 'username');
            return;
        }

        // Пользователь существует в базе, проверим, что он активен
        if (!$user['Active']) {
            $this->renderLoginPage("User $login inactive", 'username');
            return;
        }

        // Пользователь активен, проверим его пароль
        if (!self::checkPassword($_POST['password'], $user['Password'])) {
            $this->renderLoginPage('Invalid password', 'password');
            return;
        }

        // Сохраняем логин пользователя в переменных сессии
        session_regenerate_id(true);
        $_SESSION['lightauth'] = $login;

        // Запишем в таблицу dblog, что пользователь вошел
        $this->updateLastLoginDateTime($user['ID']);

        if (isset($_GET['next'])) {
            Response::redirectUrl('?' . $_GET['next']);
        } else {
            Response::redirect(ModuleRegister::getDefaultModule());
        }
    }

    protected function renderLoginPage($errorMsg = '', $errorField = '')
    {
        $tdata = [
            'errorMsg' => $errorMsg,
            'errorField' => $errorField,
            'formActionUrl' => '?a=Login'
        ];

        $tdata += $this->getRandomImage();

        if (isset($_GET['next'])) {
            $tdata['formActionUrl'] .= '&next=' . rawurlencode($_GET['next']);
        }

        Template::includeTpl('Login.tpl.php', $tdata);
        ApplicationAbstract::terminate();
    }

    protected function getRandomImage()
    {
        $tdata = [];

        $imgDir = 'i/login';
        $monthDay = date('m-d');
        if (is_dir(LIGHT_DIR_PUBLIC . "/$imgDir/$monthDay")) {
            // Если есть папка внутри i/login с именем равной текущему месяцу-дате, то используем её
            $imgDir .= "/$monthDay";
        }

        // Получим список файлов из директории
        $files = glob(LIGHT_DIR_PUBLIC . "/$imgDir/*.{jpg,png}", GLOB_BRACE);
        if ($files) {
            // Выбираем случайный файл из списка
            $rand = rand(0, count($files) - 1);
            $fileAbs = $files[$rand];
            list($width, $height) = getimagesize($fileAbs);
            $tdata['LogoWidth'] = $width;
            $tdata['LogoHeight'] = $height;
            $tdata['LogoUrl'] = "$imgDir/" . pathinfo($fileAbs, PATHINFO_BASENAME);

            // Проверим, есть ли текстовый файл для этой картинки?
            $txtFileAbs = $fileAbs . '.txt';
            if (file_exists($txtFileAbs)) {
                $tdata['LogoText'] = file_get_contents($txtFileAbs);
            }
        }

        return $tdata;
    }

    protected function checkPassword(string $postPassword, string $dbPassword)
    {
        if (Config::$encryptPasswords) {
            $salt = substr($dbPassword, 0, -32);
            return $dbPassword === User::encryptPassword($postPassword, $salt);
        } else {
            return $dbPassword === $postPassword;
        }
    }

    protected function updateLastLoginDateTime(int $userId)
    {
        $query = "INSERT INTO user_visit (UserID, LastLoginDateTime, LastRefreshDateTime)
                  VALUES ($userId, NOW(), NOW())
                  ON DUPLICATE KEY UPDATE LastLoginDateTime=NOW(), LastRefreshDateTime=NOW()";
        Db::query($query);
    }
}
