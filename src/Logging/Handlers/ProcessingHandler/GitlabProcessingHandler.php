<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler;

use InvalidArgumentException;
use LogicException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use SytxLabs\ErrorLogger\Logging\Handlers\Formatter\IssueFormatter;
use SytxLabs\ErrorLogger\Support\GitLab;
use UnexpectedValueException;

class GitlabProcessingHandler extends AbstractProcessingHandler
{
    protected string|null $url = null;
    protected string|null $apiKey = null;
    protected ?GitLab $gitLab = null;
    private string|null $errorMessage = null;

    public function __construct(int|Level|string $level, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $url = config('error-logger.gitlab.url', '');
        $apiKey = config('error-logger.gitlab.token', '');
        if (trim($url) !== '' && trim($apiKey) !== '') {
            $this->url = $url;
            $this->apiKey = $apiKey;
        } else {
            throw new InvalidArgumentException('GitLab repository url and api key must be set in the configuration file.');
        }
        $this->gitLab = new GitLab($this->url, $this->apiKey);
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->gitLab !== null) {
            $this->gitLab = null;
        }
        $this->url = null;
        $this->apiKey = null;
    }

    protected function write(LogRecord $record): void
    {
        if (($this->gitLab === null) && (trim($this->url ?? '') === '' || trim($this->apiKey ?? '') === '')) {
            throw new LogicException('Missing GitLab Repo or API Key' . Utils::getRecordMessageForException($record));
        }
        $this->errorMessage = null;
        set_error_handler([$this, 'customErrorHandler']);
        $gitLab = $this->gitLab = new GitLab($this->url, $this->apiKey);
        $this->setFormatter(new IssueFormatter('d.m.Y H:i:s T'));
        if (!$gitLab->openIssue($record->message . ' - ' . $record->datetime->format('d.m.Y H:i:s T'), $this->getFormatter()->format($record))) {
            throw new UnexpectedValueException(sprintf('The gitlab issue "%s" could not be opened: '.$this->errorMessage, $this->url) . Utils::getRecordMessageForException($record));
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function customErrorHandler(int $code, string $msg): bool
    {
        $this->errorMessage = $msg;
        return true;
    }
}
