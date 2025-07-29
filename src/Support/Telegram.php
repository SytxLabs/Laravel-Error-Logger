<?php

namespace SytxLabs\ErrorLogger\Support;

use Exception;
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
        } catch (Exception) {
            return false;
        }
        return true;
    }
}
