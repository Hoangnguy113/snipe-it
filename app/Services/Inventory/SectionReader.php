<?php

namespace App\Services\Inventory;

/**
 * Enforcement point for RB-4.
 *
 * The JSON protocol sends LOWERCASE keys: Protocol/Message.pm:44-76 lowercases
 * every key in converted()/_convert() before JSON encoding. The legacy XML
 * protocol sends UPPERCASE. GLPI's docs show uppercase because that is the XML
 * schema.
 *
 * Every read of a section or field from an inventory report MUST go through here.
 */
class SectionReader
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<int|string, mixed>
     */
    public static function section(array $content, string $name): array
    {
        foreach ($content as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return is_array($value) ? $value : [];
            }
        }

        return [];
    }

    /**
     * Always returns a list of records. The agent sends a single record as a
     * hash (e.g. 'bios') and several as an array (e.g. 'memories').
     *
     * @param  array<string, mixed>  $content
     * @return list<array<string, mixed>>
     */
    public static function rows(array $content, string $name): array
    {
        $section = self::section($content, $name);

        if ($section === []) {
            return [];
        }

        return array_is_list($section) ? $section : [$section];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function value(array $row, string $name): mixed
    {
        foreach ($row as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }
}
