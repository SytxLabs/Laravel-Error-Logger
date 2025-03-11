<?php

namespace SytxLabs\ErrorLogger\Support;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

readonly class Github
{
    private string $owner;
    private string $repo;
    private string $token;

    /**
     * @param  string  ...$args  [0] = url, [1] = token or [0] = owner, [1] = repo, [2] = token
     * @throws RuntimeException
     */
    public function __construct(string ...$args)
    {
        if (count($args) <= 0 || count($args) > 3) {
            throw new RuntimeException('Invalid number of arguments');
        }
        if (count($args) === 1 || count($args) === 2) {
            $url = parse_url($args[0]);
            if ($url === false || !isset($url['host']) || $url['host'] !== 'github.com') {
                throw new RuntimeException('Invalid github url');
            }
            $path = explode('/', $url['path']);
            if (count($path) !== 3) {
                throw new RuntimeException('Invalid github url');
            }
            $this->owner = $path[1];
            $this->repo = $path[2];
            if (str_contains($url['query'] ?? '', 'access_token')) {
                $this->token = explode('=', explode('access_token', $url['query'])[1] ?? '')[1] ?? '';
            } elseif (count($args) === 2) {
                $this->token = $args[1];
            } else {
                $this->token = '';
            }
            return;
        }
        [$this->owner, $this->repo, $this->token] = $args;
    }

    public function openIssue(string $title, string $body): bool
    {
        $url = sprintf('https://api.github.com/repos/%s/%s/issues', $this->owner, $this->repo);
        $url .= $this->addAccessToken();
        try {
            $response = $this->request()
                ->withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'title' => $title,
                    'body' => $body,
                ])
                ->throw()
                ->json();
        } catch (Exception $e) {
            return false;
        }
        return !isset($response['message']);
    }

    private function addAccessToken(): string
    {
        if (trim($this->token) !== '') {
            return '?access_token=' . $this->token;
        }
        return '';
    }

    private function request(): PendingRequest
    {
        if (trim($this->token) !== '') {
            return Http::withToken($this->token, 'token')
                ->withUserAgent('PHP');
        }
        return Http::withUserAgent('PHP');
    }
}
