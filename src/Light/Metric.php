<?php
namespace Light;

class Metric
{
    public static function record(string $actionType, int $timeMs, $payload = '')
    {
        if (!is_string($payload)) {
            $payload = json_encode($payload);
        }

        Db::insertRow([
            'CreatedBy' => User::getId(),
            'ActionType' => $actionType,
            'TimeMs' => $timeMs,
            'Payload' => $payload
        ], 'metric');
    }
}