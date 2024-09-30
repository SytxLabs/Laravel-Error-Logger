<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler;

use InvalidArgumentException;
use LogicException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use SytxLabs\ErrorLogger\Logging\Handlers\Formatter\IssueFormatter;
use SytxLabs\ErrorLogger\Support\Github;
use UnexpectedValueException;

class GithubProcessingHandler extends AbstractProcessingHandler
{
    protected string|null $url = null;
    protected string|null $apiKey = null;
    protected ?Github $github = null;
    private string|null $errorMessage = null;

    public function __construct(int|Level|string $level, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $url = config('error-logger.github.url', '');
        $apiKey = config('error-logger.github.token', '');
        if (trim($url) !== '') {
            $this->url = $url;
            $this->apiKey = $apiKey ?? '';
        } else {
            throw new InvalidArgumentException('GitHub repository url and api key must be set in the configuration file.');
        }
        $this->github = new Github($this->url, $this->apiKey);
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->github !== null) {
            $this->github = null;
        }
        $this->url = null;
        $this->apiKey = null;
        parent::close();
    }

    protected function write(LogRecord $record): void
    {
        if (($this->github === null) && trim($this->url ?? '') === '') {
            throw new LogicException('Missing GitHub Repo or API Key' . Utils::getRecordMessageForException($record));
        }
        $this->errorMessage = null;
        set_error_handler([$this, 'customErrorHandler']);
        $github = $this->github = new Github($this->url, $this->apiKey);
        $this->setFormatter(new IssueFormatter('d.m.Y H:i:s T'));
        if (!$github->openIssue($record->message . ' - ' . $record->datetime->format('d.m.Y H:i:s T'), $this->getFormatter()->format($record))) {
            throw new UnexpectedValueException(sprintf('The github issue "%s" could not be opened: '.$this->errorMessage, $this->url) . Utils::getRecordMessageForException($record));
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function customErrorHandler(int $code, string $msg): bool
    {
        $this->errorMessage = $msg;
        return true;
    }
}
