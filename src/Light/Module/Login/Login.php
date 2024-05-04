<?php

namespace Light\Module\Login;

use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\ErrorHandler;
use Light\ModuleRegister;
use Light\Request;
use Light\Response;
use Light\Template;
use Light\User;

class Login
{
    public function __invoke(): void
    {
        if (Request::getMehod() === 'POST') {

            sleep(0.5);
            $this->loginViaPost();

        } else {

            $this->renderLoginPage();

        }
    }

    /**
     * @return array
     */
    protected function checkPostData(array $postData): array
    {
        $username = trim($postData['username'] ?? '');
        if ($username === '') {
            return ['errorFieldKey' => 'username', 'errorMsg' => 'Empty Login'];
        }

        $password = trim($postData['password'] ?? '');
        if ($password === '') {
            return ['errorFieldKey' => 'password', 'errorMsg' => 'Empty Password'];
        }

        // Проверим, существует ли такой пользователь в базе?
        try {
            $user = Db::getRow("SELECT ID, Login, Active, Password FROM `user` WHERE Login = @s", $username);
        } catch (\Throwable $e) {
            $user = [];
            ErrorHandler::reportException($e);
        }
        if (!$user) {
            return ['errorFieldKey' => 'username', 'errorMsg' => "User " . htmlspecialchars($username) . " not found"];
        }

        // Пользователь существует в базе, проверим, что он активен
        if (!$user['Active']) {
            return ['errorFieldKey' => 'username', 'errorMsg' => "User " . htmlspecialchars($username) . " inactive"];
        }

        // Пользователь активен, проверим его пароль
        if (!$this->checkPassword($password, $user['Password'])) {
            return ['errorFieldKey' => 'password', 'errorMsg' => 'Invalid password'];
        }

        return ['user' => $user];
    }

    protected function loginViaPost()
    {
        $result = $this->checkPostData($_POST);
        if (isset($result['errorFieldKey'])) {
            $this->renderLoginPage($result['errorFieldKey'], $result['errorMsg']);
            return;
        }

        $this->finishSuccessLogin($result['user']['Login']);
    }

    protected function finishSuccessLogin(string $login)
    {
        $this->createNewSession($login);

        // Запишем в таблицу user_visit, что пользователь вошел
        $this->updateLastLoginDateTime($login);

        $this->redirectAfterSuccessLogin();
    }

    protected function createNewSession(string $login)
    {
        // Сохраняем логин пользователя в переменных сессии
        session_regenerate_id(true);
        $_SESSION['lightauth'] = $login;
    }

    protected function updateLastLoginDateTime(string $login)
    {
        $userId = (int)Db::getOne("SELECT ID FROM user WHERE Login = @s", $login);
        if ($userId) {
            $query = "INSERT INTO user_visit (UserID, LastLoginDateTime, LastRefreshDateTime)
                      VALUES ($userId, NOW(), NOW())
                      ON DUPLICATE KEY UPDATE LastLoginDateTime=NOW(), LastRefreshDateTime=NOW()";
            Db::query($query);
        }
    }

    protected function redirectAfterSuccessLogin()
    {
        if (isset($_GET['next'])) {
            Response::redirectUrl('?' . $_GET['next']);
        } else {
            Response::redirect(ModuleRegister::getDefaultModule());
        }
    }

    protected function renderLoginPage($errorFieldKey = '', $errorMsg = '')
    {
        $tdata = [
            'errorMsg' => $errorMsg,
            'errorField' => $errorFieldKey,
            'formActionUrl' => '?a=Login',
        ];

        $tdata += $this->getRandomImage();

        if (isset($_GET['next'])) {
            $tdata['formActionUrl'] .= '&next=' . rawurlencode((string) $_GET['next']);
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
            $rand = random_int(0, count($files) - 1);
            $fileAbs = $files[$rand];
            [$width, $height] = getimagesize($fileAbs);
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
        }

        return $dbPassword === $postPassword;
    }
}
