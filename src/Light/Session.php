<?php

namespace Light;

class Session implements \SessionHandlerInterface
{
    public static $showAsToast = false;
    private static $flash = [];

    public static function flashAdd($type, $msg)
    {
        self::$flash[] = ['type' => $type, 'msg' => $msg];
    }

    public static function flashInfo($msg)
    {
        self::flashAdd('info', $msg);
    }

    public static function flashSuccess($msg)
    {
        self::flashAdd('success', $msg);
    }

    public static function flashWarning($msg)
    {
        self::flashAdd('warning', $msg);
    }

    public static function flashError($msg)
    {
        self::flashAdd('danger', $msg);
    }

    /**
     * Без какой-либо окраски
     * @param string $msg
     */
    public static function flashNormal($msg)
    {
        self::flashAdd('normal', $msg);
    }

    public static function flashAddArr(array $messages)
    {
        foreach ($messages as $msginfo) {
            self::flashAdd($msginfo['type'], $msginfo['msg']);
        }
    }

    public static function flashGetAll()
    {
        $flashes = self::$flash;
        if ($flashes) {
            // Очищаем и в локальном массиве и в базе
            self::$flash = [];
            $sessionIdSql = Db::esc(session_id());
            Db::query("DELETE FROM phpsessionflash WHERE SessionID = '$sessionIdSql'");
        }
        return $flashes;
    }

    public function close()
    {
        return true;
    }

    public function destroy($sessionId)
    {
        $sessionIdSql = Db::esc($sessionId);
        Db::query("DELETE FROM phpsession WHERE SessionID = '$sessionIdSql'");
        Db::query("DELETE FROM phpsessionflash WHERE SessionID = '$sessionIdSql'");
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
        $flashMessagesJson = Db::getOne("SELECT Messages FROM phpsessionflash WHERE SessionID = '$sessionIdSql'");
        if ($flashMessagesJson) {
            self::$flash = json_decode($flashMessagesJson, true);
        }
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
        if (self::$flash) {
            // Есть какие-то новые flash сообщения, запишем их в таблицу phpsessionflash
            $flashMessagesJson = json_encode(self::$flash, JSON_UNESCAPED_UNICODE);
            Db::query(
                "REPLACE INTO phpsessionflash (SessionID, Messages)
             VALUES ('$sessionIdSql', '" . Db::esc($flashMessagesJson) . "')"
            );
        }

        return true;
    }
}
