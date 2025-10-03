<?php

declare(strict_types=1);

namespace App\Exception;

class RedmineException extends \RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        /** @var array<string, mixed> */
        private readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    /** @param array<string, mixed> $context */
    public static function apiError(string $message, array $context = []): self
    {
        return new self("Redmine API Error: $message", 500, null, $context);
    }

    public static function authenticationFailed(): self
    {
        return new self('Authentication failed - check your API key', 401);
    }
}
