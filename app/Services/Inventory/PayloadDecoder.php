<?php

namespace App\Services\Inventory;

class PayloadDecoder
{
    public function decode(string $body, ?string $contentType): DecodedPayload
    {
        if ($body === '') {
            throw new InvalidPayloadException('Empty body');
        }

        $plain = $this->decompress($body, $contentType);

        return new DecodedPayload($this->protocolOf($plain), $plain);
    }

    private function decompress(string $body, ?string $contentType): string
    {
        $type = strtolower(trim(explode(';', (string) $contentType)[0]));

        $attempts = match ($type) {
            'application/x-compress-zlib' => ['zlib', 'gzip', 'none'],
            'application/x-compress-gzip' => ['gzip', 'zlib', 'none'],
            // Missing or wrong Content-Type: read it as-is first, then guess the
            // compression. Some agent setups send the wrong header.
            default => ['none', 'zlib', 'gzip'],
        };

        foreach ($attempts as $how) {
            $out = match ($how) {
                'zlib' => @gzuncompress($body),
                'gzip' => @gzdecode($body),
                'none' => $body,
            };

            if (is_string($out) && $out !== '' && $this->looksLikePayload($out)) {
                return $out;
            }
        }

        throw new InvalidPayloadException(
            'Cannot decode body. Content-Type: '.($contentType ?: '(none)')
            .', '.strlen($body).' bytes, starts with: '.bin2hex(substr($body, 0, 8))
        );
    }

    private function looksLikePayload(string $plain): bool
    {
        $head = ltrim($plain);

        return str_starts_with($head, '{') || str_starts_with($head, '<');
    }

    private function protocolOf(string $plain): string
    {
        return str_starts_with(ltrim($plain), '{')
            ? DecodedPayload::PROTOCOL_JSON
            : DecodedPayload::PROTOCOL_XML;
    }
}
