<?php

namespace Modules\MediaRadar\Sources\Support;

use RuntimeException;

class ProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $provider = '',
        public readonly ?int $statusCode = null,
        public readonly bool $retryable = true,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message, $statusCode ?? 0);
    }

    public static function fromStatus(string $provider, int $status, string $body = ''): self
    {
        $permanent = in_array($status, [400, 401, 403, 404], true);

        // 403 from YouTube is normally quota exhaustion, which is not permanent
        // for the video itself but must not be retried in a tight loop.
        return new self(
            sprintf('%s API responded with HTTP %d. %s', ucfirst($provider), $status, mb_substr($body, 0, 500)),
            $provider,
            $status,
            ! $permanent,
        );
    }
}
