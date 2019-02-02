<?php

namespace Light;

/**
 * @method static flashInfo(string $msg)
 * @method static flashSuccess(string $msg)
 * @method static flashWarning(string $msg)
 * @method static flashError(string $msg)
 * @method static flashNormal(string $msg)
 */
class Session implements \SessionHandlerInterface
{
    public static $showAsToast = false;

    /** @var Msg */
    private static $msgBuff;

    public static function getMsgBuff()
    {
        if (!self::$msgBuff) {
            self::$msgBuff = new Msg();
        }
        return self::$msgBuff;
    }

    public static function flashAdd(string $type, string $msg)
    {
        self::getMsgBuff()->add($type, $msg);
    }

    public static function __callStatic($name, $arguments)
    {
        if (strpos($name, 'flash') === 0) {
            $type = strtolower(substr($name, 5));
            self::flashAdd($type, $arguments[0] ?? '');
            return;
        }
        throw new \InvalidArgumentException("Unknown method Session::$name");
    }

    public static function flashGetAllAndClear(): array
    {
        $out = [];
        if (self::$msgBuff) {
            // Очищаем и в локальном буфере сообщений
            $out = self::getMsgBuff()->getAll();
            self::$msgBuff = new Msg();
        }

        // Очищаем в базе
        Db::query("DELETE FROM user_flash WHERE UserID = " . User::getId());

        return $out;
    }

    public function close()
    {
        return true;
    }

    public function destroy($sessionId)
    {
        $sessionIdSql = Db::esc($sessionId);
        Db::query("DELETE FROM phpsession WHERE SessionID = '$sessionIdSql'");
        return true;
    }

    public function gc($maxlifetime)
    {
        Db::query("DELETE FROM phpsession WHERE CreatedTime < " . (time() - (int)$maxlifetime));
        return true;
    }

    public function open($savePath, $name)
    {
        return true;
    }

    private static $lastSessionData;

    public function read($sessionId)
    {
        $sessionIdSql = Db::esc($sessionId);
        self::$lastSessionData = (string)Db::getOne("SELECT `Data` FROM phpsession WHERE SessionID = '$sessionIdSql'");
        return self::$lastSessionData;
    }

    public function write($sessionId, $sessionData)
    {
        $sessionIdSql = Db::esc($sessionId);
        if (self::$lastSessionData !== $sessionData) {
            Db::query(
                "REPLACE INTO phpsession (SessionID, CreatedTime, `Data`)
             VALUES ('$sessionIdSql', " . time() . ", '" . Db::esc($sessionData) . "')"
            );
        }

        return true;
    }

    public static function loadFlash()
    {
        if (!User::getId()) {
            return;
        }
        $flashMessagesJson = Db::getOne("SELECT Messages FROM user_flash WHERE UserID = " . User::getId());
        if ($flashMessagesJson) {
            $flashMessagess = json_decode($flashMessagesJson, true);
            if (is_array($flashMessagess)) {
                self::getMsgBuff()->addMessagess($flashMessagess);
            }
        }
    }

    public static function writeFlash()
    {
        if (self::$msgBuff && count(self::$msgBuff) && User::getId()) {
            // Есть какие-то новые flash сообщения, запишем их в таблицу phpsessionflash
            $flashMessagesJson = json_encode(self::$msgBuff->getAll(), JSON_UNESCAPED_UNICODE);
            Db::query(
                "REPLACE INTO user_flash (UserID, Messages)
                 VALUES (" . User::getId() . ", '" . Db::esc($flashMessagesJson) . "')"
            );
        }
    }
}
