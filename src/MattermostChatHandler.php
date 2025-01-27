<?php

namespace Enigma;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class MattermostChatHandler extends AbstractProcessingHandler
{
    /**
     * Sends a log record to the configured Mattermost chat channel if the log level meets or exceeds the specified threshold.
     *
     * @param LogRecord $record - The log record containing attributes such as level, message, and context.
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        $params = $record->toArray();
        if ($this->isErrorLevelOrAbove($params['level'])) {
            $this->sendToChannel($record);
        }
    }

    /**
     * Determines if the given log level is at or above the configured error level.
     *
     * @param int $level The log level to check.
     * @return bool True if the log level is equal to or higher than the configured error level, false otherwise.
     */
    private function isErrorLevelOrAbove(int $level): bool
    {
        return $level >= Config::get('logging.channels.mattermost-chat.error_level');
    }

    /**
     * Sends the formatted log record to the configured Mattermost chat channel.
     *
     * @param LogRecord $record The log record containing message, datetime, and formatted properties.
     * @return void
     */
    private function sendToChannel(LogRecord $record): void
    {
        Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(Config::get('logging.channels.mattermost-chat.url'), $this->getRequestBody($record));
    }


    /**
     * Builds and returns the request body for the Mattermost chat logging channel.
     *
     * @param $record - The log record containing message, datetime, and formatted properties.
     * @return array The structured request body to send to Mattermost chat.
     */
    protected function getRequestBody($record): array
    {
        $configuredTimezone = $this->getConfiguredTimezone();
        $text = $this->prepareText($record, $configuredTimezone);

        return ['text' => $text];
    }

    /**
     * Retrieves the configured timezone for the Mattermost chat logging channel.
     *
     * @return string The timezone value from the configuration, or a default value of 'Asia/Kolkata' if not set.
     */
    private function getConfiguredTimezone(): string
    {
        $timezone = Config::get('logging.channels.mattermost-chat.timezone');
        return !empty($timezone) ? $timezone : 'Asia/Kolkata';
    }

    /**
     * Prepares and formats the text to be sent with log details.
     *
     * @param $record - The log record containing message, datetime, and formatted properties.
     * @param $configuredTimezone - The timezone to which the datetime should be converted.
     * @return string The formatted string containing the log message and metadata.
     */
    private function prepareText($record, $configuredTimezone): string
    {
        $dateTime = Carbon::parse(strtotime($record->datetime))
            ->timezone($configuredTimezone)
            ->format('Y-m-d h:i: A');

        $text = "<!channel> **Error: " . $record->message;
        $text .= "\n" . "Date&Time: " . $dateTime . "**";
        $text .= "\n" . $this->getLevelContent($record);

        return $text;
    }

    /**
     * Extracts and returns a truncated string representation of the log record content.
     *
     * @param $record - The log record containing formatted properties.
     * @return string The truncated string representation of the log record content, limited to 38,000 characters.
     */
    protected function getLevelContent($record): string
    {
        return substr($record->formatted, 0, 38000);
    }
}
