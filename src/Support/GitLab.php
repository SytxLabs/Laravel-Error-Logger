<?php

namespace SytxLabs\ErrorLogger\Support;

use Exception;
use Gitlab\Client;
use RuntimeException;

class GitLab
{
    private readonly string $url;
    private readonly string $token;
    private readonly string $project;

    /**
     * @param  string  ...$args [0] = url, [1] = token or [0] = url, [1] = project, [2] = token or [0] = url
     */
    public function __construct(string ...$args)
    {
        if (count($args) <= 1 || count($args) > 3) {
            throw new RuntimeException('Invalid number of arguments');
        }
        if (count($args) === 2) {
            $url = parse_url($args[0]);
            if ($url === false || !isset($url['host'])) {
                throw new RuntimeException('Invalid gitlab url');
            }
            $path = array_values(array_filter(explode('/', $url['path']), static fn ($value) => trim($value) !== ''));
            if (count($path) !== 2) {
                throw new RuntimeException('Invalid gitlab url');
            }
            $this->url = $url['scheme'] . '://' . $url['host'];
            $this->project = $path[0] . '/' . $path[1];
            $this->token = $args[1] ?? '';
            return;
        }
        [$this->url, $this->project, $this->token] = $args;
    }

    public function getClient(): Client
    {
        $client = new Client();
        $client->setUrl($this->url);
        $client->authenticate($this->token, Client::AUTH_HTTP_TOKEN);
        return $client;
    }

    public function openIssue(string $title, string $body): bool
    {
        try {
            $client = $this->getClient();
            $project = $client->projects()->show($this->project);
            $client->issues()->create($project['id'], [
                'title' => $title,
                'description' => $body,
            ]);
        } catch (Exception) {
            return false;
        }
        return true;
    }
}
