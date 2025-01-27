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
     * Write a log record to the Mattermost chat channel.
     *
     * @param LogRecord $record The log record to be written.
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        if ($record->level >= Config::get('logging.channels.mattermost-chat.error_level')) {
            Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post(Config::get('logging.channels.mattermost-chat.url'), $this->getRequestBody($record));
        }
    }

    /**
     * Builds and returns the request body for the Mattermost chat logging channel.
     *
     * @param $record - The log record containing message, datetime, and formatted properties.
     * @return array The structured request body to send to Mattermost chat.
     */
    protected function getRequestBody($record): array
    {
        $timezone = (Config::get('logging.channels.mattermost-chat.timezone') != null && !empty(Config::get('logging.channels.mattermost-chat.timezone'))) ? Config::get('logging.channels.mattermost-chat.timezone') : 'Asia/Kolkata';
        return [
            'text' => "<!channel> **Error: " . $record->message . "Date&Time: " . Carbon::parse(strtotime($record->datetime))->timezone($timezone)->format('Y-m-d h:i: A') . "** \n" . $this->getLevelContent($record)

        ];
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
