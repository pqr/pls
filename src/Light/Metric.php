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

        self::newrelicMetric($actionType, $timeMs);
    }

    public static function newrelicMetric($metricName, $timeMs)
    {
        if (extension_loaded('newrelic')) {
            // https://docs.newrelic.com/docs/agents/php-agent/php-agent-api/newreliccustommetric-php-agent-api
            // Ensure PHP agent is available
            newrelic_custom_metric("Custom/$metricName", $timeMs);
        }
    }

    public static function newrelicEvent($eventName, array $attributes = [])
    {
        if (extension_loaded('newrelic')) { // Ensure PHP agent is available
            newrelic_record_custom_event("Custom/$eventName", $attributes);
        }
    }

}