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
                "INSERT INTO phpsession (SessionID, CreatedTime, `Data`)
                 VALUES ('$sessionIdSql', " . time() . ", '" . Db::esc($sessionData) . "')
                 ON DUPLICATE KEY UPDATE SessionID = VALUES(SessionID), `Data` = VALUES(`Data`)"
            );
        }

        return true;
    }

    public static function flashGetAllAndClear(): array
    {
        if (!User::getId()) {
            return [];
        }

        $out = [];

        // Загрузим всё, что было накоплено в базе
        $userId = User::getId();
        $flashFromDb = Db::getAll("SELECT ID, Messages FROM user_flash WHERE UserID = $userId ORDER BY ID");
        if ($flashFromDb) {
            foreach ($flashFromDb as $jsonFromDb) {
                $messagesFromDb = json_decode($jsonFromDb['Messages'], true);
                if (is_array($messagesFromDb)) {
                    $out = array_merge($out, $messagesFromDb);
                }
            }
            Db::query("DELETE FROM user_flash WHERE ID IN (@a)", array_column($flashFromDb, 'ID'));
        }

        if (self::$msgBuff) {
            // Очищаем и в локальном буфере сообщений
            $out = array_merge($out, self::getMsgBuff()->getAll());
            self::$msgBuff = new Msg();
        }

        return $out;
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

    public static function writeFlash()
    {
        if (self::$msgBuff && count(self::$msgBuff) && User::getId()) {
            // Есть какие-то новые flash сообщения, запишем их в таблицу user_flash
            $flashMessagesJson = json_encode(self::$msgBuff->getAll(), JSON_UNESCAPED_UNICODE);
            Db::query(
                "INSERT INTO user_flash (UserID, Messages)
                 VALUES (" . User::getId() . ", " . Db::escString($flashMessagesJson) . ")"
            );
        }
    }
}
