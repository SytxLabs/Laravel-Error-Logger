<?php

namespace SytxLabs\ErrorLogger\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

readonly class WhatsAppCallMeBot
{
    private const ApiUrl = 'https://api.callmebot.com/whatsapp.php?source=php&phone={phone}&text={text}&apikey={apikey}';

    public function __construct(private string $apiKey, private string $phoneNumber)
    {
    }

    public function send(string $text, ?string $phoneNumber = null): bool
    {
        if (str_contains($this->apiKey, ',')) {
            $apiKeys = explode(',', $this->apiKey);
            foreach ($apiKeys as $apiKey) {
                $url = str_replace(
                    ['{phone}', '{text}', '{apikey}'],
                    [$this->phoneNumber, urlencode($text), $apiKey],
                    self::ApiUrl
                );
                Http::get($url)->status();
            }
            return 200;
        }
        $url = str_replace(
            ['{phone}', '{text}', '{apikey}'],
            [$phoneNumber ?? $this->phoneNumber, urlencode($text), $this->apiKey],
            self::ApiUrl
        );
        try {
            Http::get($url)->throw()->status();
        } catch (Throwable) {
        }
        return false;
    }
}
