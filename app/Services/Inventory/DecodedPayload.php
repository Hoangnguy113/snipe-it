<?php

namespace App\Services\Inventory;

readonly class DecodedPayload
{
    public const PROTOCOL_JSON = 'json';

    public const PROTOCOL_XML = 'xml';

    public function __construct(
        public string $protocol,
        public string $content,
    ) {}

    public function isJson(): bool
    {
        return $this->protocol === self::PROTOCOL_JSON;
    }

    /**
     * JSON protocol only. The XML protocol is handled by its own parsers.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if (! $this->isJson()) {
            throw new InvalidPayloadException('toArray() is only valid for the JSON protocol');
        }

        $decoded = json_decode($this->content, true);

        if (! is_array($decoded)) {
            throw new InvalidPayloadException('Invalid JSON content: '.json_last_error_msg());
        }

        return $decoded;
    }
}
