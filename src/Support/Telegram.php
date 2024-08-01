<?php

namespace SytxLabs\ErrorLogger\Support;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

readonly class Telegram
{
    public function __construct(private string $token, private string $chatId)
    {
    }

    public function send(string $message, ?string $chatId = null): bool
    {
        try {
            Http::get('https://api.telegram.org/bot' . $this->token . '/sendMessage', [
                'chat_id' => $chatId ?? $this->chatId,
                'text' => $message,
            ])->throw();
        } catch (RequestException) {
            return false;
        }
        return true;
    }
}
