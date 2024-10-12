<?php

namespace SytxLabs\ErrorLogger\Enums;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LogicException;
use Monolog\LogRecord;

enum WebhookFormat: string
{
    case Json = 'json';
    case Form = 'form';
    case Xml = 'xml';
    case None = 'none';

    public function send(string $url, string $method, ?string $secretType, array|string|null $secret, LogRecord $record): void
    {
        $method = strtolower($method);
        match ($this) {
            self::Json => $this->sendJson($url, $method, $secretType, $secret, $record),
            self::Form => $this->sendForm($url, $method, $secretType, $secret, $record),
            self::Xml => $this->sendXml($url, $method, $secretType, $secret, $record),
            self::None => $this->sendNone($url, $method, $secretType, $secret),
        };
    }

    private function setSecretHeader(PendingRequest $http, ?string $secretType, array|string|null $secret): PendingRequest
    {
        if ($secretType === null || $secret === null) {
            return $http;
        }
        $secretType = strtolower($secretType);
        if ($secretType === 'bearer') {
            return $http->withToken($secret);
        }
        if ($secretType === 'basic' && is_array($secret) && count($secret) === 2) {
            return $http->withBasicAuth($secret[0], $secret[1]);
        }
        return $http;
    }

    private function sendJson(string $url, string $method, ?string $secretType, array|string|null $secret, LogRecord $record): void
    {
        $http = $this->setSecretHeader(Http::asJson(), $secretType, $secret);
        $http->$method($url, [
            'message' => $record->message,
            'time' => $record->datetime->format('Y-m-d H:i:s'),
            'channel' => $record->channel,
            'level' => $record->level->getName(),
            'context' => $record->context,
            'extra' => $record->extra,
        ]);
    }

    private function sendForm(string $url, string $method, ?string $secretType, array|string|null $secret, LogRecord $record): void
    {
        $http = $this->setSecretHeader(Http::asForm(), $secretType, $secret);
        $http->$method($url, [
            'message' => $record->message,
            'time' => $record->datetime->format('Y-m-d H:i:s'),
            'channel' => $record->channel,
            'level' => $record->level->getName(),
            'context' => $record->context,
            'extra' => $record->extra,
        ]);
    }

    private function sendXml(string $url, string $method, ?string $secretType, array|string|null $secret, LogRecord $record): void
    {
        if (!extension_loaded('simplexml')) {
            throw new LogicException('The SimpleXML extension is required to send logs in XML format.');
        }
        $xml = new \SimpleXMLElement('<record/>');
        $xml->addChild('message', $record->message);
        $xml->addChild('time', $record->datetime->format('Y-m-d H:i:s'));
        $xml->addChild('channel', $record->channel);
        $xml->addChild('level', $record->level->getName());
        $context = $xml->addChild('context');
        foreach ($record->context as $key => $value) {
            $context?->addChild($key, $value);
        }
        $extra = $xml->addChild('extra');
        foreach ($record->extra as $key => $value) {
            $extra?->addChild($key, $value);
        }
        $xmlString = $xml->asXML();
        if (!is_string($xmlString)) {
            throw new LogicException('Failed to convert XML to string.');
        }
        $http = $this->setSecretHeader(Http::withHeader('Content-Type', 'text/xml'), $secretType, $secret);
        $http->$method($url, $xmlString);
    }

    private function sendNone(string $url, string $method, ?string $secretType, array|string|null $secret): void
    {
        $this->setSecretHeader(Http::withHeaders([]), $secretType, $secret)->$method($url);
    }
}
