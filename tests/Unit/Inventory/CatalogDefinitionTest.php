<?php

namespace Tests\Unit\Inventory;

use App\Models\CustomField;
use Tests\TestCase;

class CatalogDefinitionTest extends TestCase
{
    public function test_has_sixteen_entries_covering_the_glpi_menu(): void
    {
        $entries = config('inventory_catalog.entries');

        $this->assertCount(16, $entries);
        $this->assertSame([
            'computers', 'monitors', 'software', 'network_equipment', 'peripherals',
            'printers', 'cartridges', 'consumables', 'phones', 'racks', 'enclosures',
            'pdus', 'passive_equipment', 'unmanaged', 'cables', 'simcards',
        ], array_keys($entries));
    }

    public function test_every_entry_field_key_exists_in_the_field_pool(): void
    {
        $pool = config('inventory_catalog.fields');

        foreach (config('inventory_catalog.entries') as $key => $entry) {
            foreach ($entry['fields'] ?? [] as $fieldKey) {
                $this->assertArrayHasKey($fieldKey, $pool, "Mục '{$key}' dùng trường '{$fieldKey}' không có trong kho trường");
            }
        }
    }

    public function test_only_asset_entries_declare_fields(): void
    {
        foreach (config('inventory_catalog.entries') as $key => $entry) {
            $this->assertContains($entry['type'], ['asset', 'consumable', 'license'], "Mục '{$key}' có type lạ");

            if ($entry['type'] !== 'asset') {
                $this->assertEmpty($entry['fields'] ?? [], "Mục '{$key}' không phải tài sản nên không được có bộ trường");
            }
        }
    }

    public function test_category_names_are_unique_per_type(): void
    {
        $seen = [];

        foreach (config('inventory_catalog.entries') as $key => $entry) {
            $id = $entry['type'].'|'.mb_strtolower($entry['category']);
            $this->assertArrayNotHasKey($id, $seen, "Danh mục '{$entry['category']}' bị trùng ở mục '{$key}'");
            $seen[$id] = true;
        }
    }

    public function test_field_names_are_unique_ignoring_case(): void
    {
        $seen = [];

        foreach (config('inventory_catalog.fields') as $key => $field) {
            $name = mb_strtolower($field['name']);
            $this->assertArrayNotHasKey($name, $seen, "Tên trường '{$field['name']}' bị trùng ở '{$key}'");
            $seen[$name] = true;
        }
    }

    public function test_every_field_is_valid_for_snipe_it_custom_fields(): void
    {
        foreach (config('inventory_catalog.fields') as $key => $field) {
            $this->assertContains($field['element'], CustomField::ELEMENT_KEYS, "Trường '{$key}': element không hợp lệ");

            if (isset($field['format'])) {
                $this->assertArrayHasKey($field['format'], CustomField::PREDEFINED_FORMATS, "Trường '{$key}': format không hợp lệ");
            }

            if (CustomField::elementRequiresFieldValues($field['element'])) {
                $this->assertNotEmpty(trim($field['field_values'] ?? ''), "Trường '{$key}': thiếu field_values");
            }

            if (! empty($field['encrypted'])) {
                $this->assertTrue(
                    CustomField::canEncryptFor($field['element'], $field['format'] ?? 'ANY'),
                    "Trường '{$key}': không mã hoá được với element/format này"
                );
            }
        }
    }

    public function test_every_field_in_the_pool_is_used_by_at_least_one_entry(): void
    {
        $used = collect(config('inventory_catalog.entries'))->pluck('fields')->flatten()->unique()->all();

        foreach (array_keys(config('inventory_catalog.fields')) as $key) {
            $this->assertContains($key, $used, "Trường '{$key}' không được mục nào dùng");
        }
    }

    public function test_sim_secrets_are_encrypted(): void
    {
        foreach (['sim_pin', 'sim_pin2', 'sim_puk', 'sim_puk2'] as $key) {
            $this->assertTrue(config("inventory_catalog.fields.{$key}.encrypted"), "{$key} phải được mã hoá");
        }
    }
}
