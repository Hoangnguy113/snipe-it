<?php

namespace Tests\Unit\Inventory;

use App\Services\Inventory\InvalidPayloadException;
use App\Services\Inventory\PayloadDecoder;
use PHPUnit\Framework\TestCase;

class PayloadDecoderTest extends TestCase
{
    private PayloadDecoder $decoder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->decoder = new PayloadDecoder;
    }

    // Perl's Compress::Zlib::compress() emits an RFC1950 zlib stream - PHP reads
    // it with gzcompress/gzuncompress (NOT gzencode/gzdecode).
    public function test_decodes_zlib_compressed_json(): void
    {
        $json = '{"action":"contact","deviceid":"PC-045"}';

        $result = $this->decoder->decode(gzcompress($json), 'application/x-compress-zlib');

        $this->assertSame('json', $result->protocol);
        $this->assertSame($json, $result->content);
        $this->assertTrue($result->isJson());
        $this->assertSame('contact', $result->toArray()['action']);
    }

    public function test_decodes_gzip_compressed_xml(): void
    {
        $xml = '<?xml version="1.0"?><REQUEST><QUERY>PROLOG</QUERY></REQUEST>';

        $result = $this->decoder->decode(gzencode($xml), 'application/x-compress-gzip');

        $this->assertSame('xml', $result->protocol);
        $this->assertSame($xml, $result->content);
        $this->assertFalse($result->isJson());
    }

    public function test_accepts_uncompressed_body_when_no_content_type(): void
    {
        $json = '{"action":"contact"}';

        $result = $this->decoder->decode($json, null);

        $this->assertSame('json', $result->protocol);
        $this->assertSame($json, $result->content);
    }

    // Some agent setups send zlib without the right Content-Type. Sniff the
    // bytes instead of giving up.
    public function test_sniffs_zlib_when_content_type_lies(): void
    {
        $json = '{"action":"inventory"}';

        $result = $this->decoder->decode(gzcompress($json), 'text/plain');

        $this->assertSame($json, $result->content);
    }

    public function test_ignores_charset_suffix_in_content_type(): void
    {
        $json = '{"action":"contact"}';

        $result = $this->decoder->decode(gzcompress($json), 'application/x-compress-zlib; charset=utf-8');

        $this->assertSame($json, $result->content);
    }

    public function test_throws_on_body_that_is_neither_json_nor_xml_nor_compressed(): void
    {
        $this->expectException(InvalidPayloadException::class);

        $this->decoder->decode("\x01\x02\x03garbage", 'application/x-compress-zlib');
    }

    public function test_throws_on_empty_body(): void
    {
        $this->expectException(InvalidPayloadException::class);

        $this->decoder->decode('', null);
    }
}
