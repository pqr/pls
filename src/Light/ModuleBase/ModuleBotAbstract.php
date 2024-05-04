<?php

namespace Light\ModuleBase;


use Light\Config;
use Light\Db;
use Light\Response;
use Light\User;

abstract class ModuleBotAbstract
{
    protected $telegramId;

    public function __invoke(): void
    {
        $token = $_GET['token'] ?? '';
        if (!$token) {
            $this->sendResponse('Ошибка: не передан token.', 403);
            return;
        }

        if ($token !== Config::$telegramBotToken) {
            $this->sendResponse('Ошибка: не верный token.', 403);
            return;
        }

        $this->telegramId = (int)($_GET['telegramid'] ?? 0);
        if (!$this->telegramId) {
            $this->sendResponse('Ошибка: не передан telegramid. Попробуйте отправить запрос повторно.', 400);
            return;
        }

        $this->loadUserByTelegramId($this->telegramId);

        $text = $_GET['text'] ?? '';
        if ($text === '' || stripos($text, '/start') === 0) {

            $startMessage = User::isAuth()
                ? $this->getStartMessageAuthorized()
                : $this->getStartMessageNotAuthorized();

            $this->sendResponse($startMessage);
            return;
        }

        // Далее проверим, возможно пользователь хочет переавторизоваьться?
        if (stripos($text, '/login') === 0) {
            $token = explode(' ', $text, 2)[1] ?? '';
            if (!$this->loadUserByToken($token, $this->telegramId)) {
                $msg = "Не верное секретное слово переданное после /login\nВы можете посмотреть своё секретное слово в базе в разделе Инструкции.";
                $this->sendResponse($msg, 403);
            } else {
                $this->sendResponse($this->getStartMessageAuthorized());
            }
            return;
        }

        // Наконец, если это был не запрос /login и не запрос /start, то проверим, залогине ли пользователь в итоге?
        if (!User::isAuth()) {
            $this->sendResponse($this->getStartMessageNotAuthorized());
        }

        // Теперь разобьём результат на команды и аргументы
        $text = trim($text);
        $text = str_replace('  ', ' ', $text);
        $parts = array_values(array_filter(explode(' ', $text)));

        // Если пользователь ввёл всего одно слово и это какая-то команда начинающаяся со слеш
        // скорее всего пользователь воспользовался автокомплитом телеграма и нужно теперь предложить ввести аргумент
        if (count($parts) === 1 && $parts[0][0] === '/') {
            $theOnlyCommand = ltrim($parts[0], '/');

            // Отправим пользователю сообщение-приглашение ко вводу аргумента
            $message = $this->getWizardMessageForCommand($theOnlyCommand);
            if ($message) {

                // Запишем в базу данных текущую команду, чтобы при следующем сообщении приклеить аргумент к этой команде
                Db::query("UPDATE user_telegramid SET ActiveCommand = " . Db::escString($theOnlyCommand) . " WHERE TelegramID = " . $this->telegramId);

                $this->sendResponse($message);
                return;
            }

        }

        // Проверим, может у пользователя в базе есть ActiveCommand?
        // тогда нужно добавить эту команду в самое начало
        $activeCommand = Db::getOne("SELECT ActiveCommand FROM user_telegramid WHERE TelegramID = " . $this->telegramId);
        if ($activeCommand) {
            Db::query("UPDATE user_telegramid SET ActiveCommand = '' WHERE TelegramID = " . $this->telegramId);
            array_unshift($parts, $activeCommand);
        }

        $commands = [];
        for($i = 0; $i < count($parts); $i += 2) {
            if ($parts[$i][0] === '/' || isset($parts[$i + 1])) {
                $theOnlyCommand = mb_strtolower(ltrim($parts[$i], '/'));
                $arg = $parts[$i + 1] ?? '';
            } else {
                $theOnlyCommand = '';
                $arg = $parts[$i];
            }
            $commands[] = [$theOnlyCommand, $arg];
        }

        $this->runCommands($commands);

    }

    abstract protected function runCommands(array $commands);

    abstract protected function getWizardMessageForCommand(string $command);

    public function sendResponse(string $message, $status = 200): void
    {
        Db::insertRow([
            'CreatedBy' => User::getId(),
            'TelegramID' => $this->telegramId,
            'Query' => $_GET['text'] ?? '',
            'Response' => $message
        ], 'log_telegram');
        Response::json(['basis_message' => $message], $status);
    }

    protected function loadUserByTelegramId(int $telegramId)
    {
        $userLogin = Db::getOne("
            SELECT u.Login
            FROM user u, user_telegramid ut
            WHERE u.ID = ut.UserID
                AND u.Active = 1
                AND u.TelegramToken != ''
                AND ut.TelegramID = $telegramId 
        ");
        if (!$userLogin) {
            return false;
        }

        return User::loadAsCurrent($userLogin);
    }

    protected function loadUserByToken(string $token, int $telegramId)
    {
        $userLogin = Db::getOne("
            SELECT u.Login
            FROM user u
            WHERE u.Active = 1
                AND u.TelegramToken != ''
                AND u.TelegramToken = @s
        ", $token);
        if (!$userLogin) {
            return false;
        }

        if (!User::loadAsCurrent($userLogin)) {
            return false;
        }

        // Пользователь успешно загружен, запишем его telgramid в специальную таблицу
        $userId = User::getId();
        Db::query("REPLACE INTO user_telegramid (TelegramID, UserID) VALUES ($telegramId, $userId)");

        return true;

    }

    protected function getStartMessageAuthorized()
    {
        return 'Добро пожаловать, ' . User::getName();
    }

    protected function getStartMessageNotAuthorized()
    {
        return "Вы не авторизованы.\nИспользуйте команду /login <секретное слово>\nСекретное слово можно посмотреть в базе в разделе Инструкции.";
    }
}
