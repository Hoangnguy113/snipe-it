<?php

namespace Tests\Unit\Inventory;

use App\Services\Inventory\SectionReader;
use PHPUnit\Framework\TestCase;

class SectionReaderTest extends TestCase
{
    // RB-4: the JSON protocol sends LOWERCASE keys (Protocol/Message.pm:44-76
    // lowercases every key before encoding), the XML protocol sends UPPERCASE.
    // Every read MUST go through SectionReader.
    public function test_reads_lowercase_keys_from_json_protocol(): void
    {
        $content = ['memories' => [['slot' => '0', 'capacity' => 8192]]];

        $rows = SectionReader::rows($content, 'MEMORIES');

        $this->assertCount(1, $rows);
        $this->assertSame(8192, SectionReader::value($rows[0], 'CAPACITY'));
    }

    public function test_reads_uppercase_keys_from_xml_protocol(): void
    {
        $content = ['MEMORIES' => [['SLOT' => '0', 'CAPACITY' => 8192]]];

        $rows = SectionReader::rows($content, 'memories');

        $this->assertCount(1, $rows);
        $this->assertSame(8192, SectionReader::value($rows[0], 'capacity'));
    }

    // The agent sends a single record as a hash and several as an array.
    // rows() must always return a list.
    public function test_wraps_a_single_record_into_a_list(): void
    {
        $content = ['bios' => ['ssn' => 'ABC123']];

        $rows = SectionReader::rows($content, 'bios');

        $this->assertCount(1, $rows);
        $this->assertSame('ABC123', SectionReader::value($rows[0], 'SSN'));
    }

    public function test_returns_empty_list_for_missing_section(): void
    {
        $this->assertSame([], SectionReader::rows(['hardware' => []], 'monitors'));
    }

    public function test_returns_null_for_missing_field(): void
    {
        $this->assertNull(SectionReader::value(['ssn' => 'X'], 'not-there'));
    }
}
