<?php

namespace AdminFlow\CMS\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ConcurrencyConflictException extends RuntimeException
{
    protected int $expectedVersion;
    protected int $currentVersion;

    public function __construct(
        int $expectedVersion,
        int $currentVersion,
        string $message = '内容已被他人修改，保存存在冲突，请刷新后重试。',
    ) {
        parent::__construct($message, Response::HTTP_CONFLICT);
        $this->expectedVersion = $expectedVersion;
        $this->currentVersion = $currentVersion;
    }

    public function getExpectedVersion(): int
    {
        return $this->expectedVersion;
    }

    public function getCurrentVersion(): int
    {
        return $this->currentVersion;
    }
}
