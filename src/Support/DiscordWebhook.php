<?php

namespace SytxLabs\ErrorLogger\Support;

use Exception;
use Illuminate\Support\Facades\Http;

class DiscordWebhook
{
    private string $webhookUrl;
    private string $txt = 'Error Logger';
    private string $title = 'Discord Webhook';
    private string $avatar = '';
    private string $username;
    private string $hex = '3366ff';
    private string $footer = '';

    public function __construct(string $url = '')
    {
        $this->username = config('app.name', 'Error Logger');
        $this->webhookUrl = $url === '' ? config('discord.webhook.defaultUrl') : $url;
    }

    public function setTxt(string $txt): self
    {
        $this->txt = $txt;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setColor(string $hex): self
    {
        if (str_starts_with($hex, '#')) {
            $hex = substr($hex, 1);
        }
        $this->hex = $hex;
        return $this;
    }

    public function sendEmbed(?string $dateTime = null): bool
    {
        try {
            return Http::post($this->webhookUrl, [
                'username' => $this->username,
                'tts' => false,
                'avatar_url' => $this->avatar,
                'embeds' => [
                    [
                        'title' => $this->title,
                        'type' => 'rich',
                        'description' => $this->txt,
                        'timestamp' => $dateTime ?? date('c'),
                        'color' => hexdec($this->hex),

                        // Footer
                        'footer' => [
                            'text' => $this->footer,
                        ],
                    ],
                ],
            ])->throw()->successful();
        } catch (Exception) {
        }
        return false;
    }
}
