<?php
namespace Light;

class Metric
{
    public static function record(string $actionType, int $timeMs, $payload = ''): void
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

    public static function newrelicMetric($metricName, $timeMs): void
    {
        if (extension_loaded('newrelic')) {
            // https://docs.newrelic.com/docs/agents/php-agent/php-agent-api/newreliccustommetric-php-agent-api
            // Ensure PHP agent is available
            newrelic_custom_metric("Custom/$metricName", $timeMs);
        }
    }

    /**
     * https://docs.newrelic.com/docs/insights/insights-data-sources/custom-data/insights-custom-data-requirements-limits
     * https://docs.newrelic.com/docs/insights/insights-data-sources/custom-data/insert-custom-events-insights-api#json-format
     *
     * Naming syntax
     * Event and attribute names can be a combination of alphanumeric characters, underscores _, and colons :.
     * Do not use words reserved for use by NRQL and Insights.
     *
     * @param string $eventName Can be a combination of alphanumeric characters, _ underscores, and : colons.
     */
    public static function newrelicEvent(string $eventName, array $attributes = []): void
    {
        if (extension_loaded('newrelic')) { // Ensure PHP agent is available
            newrelic_record_custom_event("Custom_$eventName", $attributes);
        }
    }

}
