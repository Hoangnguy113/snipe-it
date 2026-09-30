# GĐ8 — Danh mục thiết bị ngang menu GLPI — Kế hoạch thực thi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cài sẵn trong Snipe-IT v8.7.2 đủ danh mục, bộ trường tùy chỉnh và mô-đen mẫu cho 16 mục tài sản/vật tư của menu GLPI, kèm một trang chỉ mục `/inventory/catalog`, để tạo được thiết bị mỗi loại qua giao diện, API và nhập CSV.

**Architecture:** Toàn bộ định nghĩa là dữ liệu thuần trong `config/inventory_catalog.php`. Một lớp `CatalogInstaller` đọc định nghĩa đó và tạo (idempotent) `Category` → `CustomField` → `CustomFieldset` → `AssetModel` mẫu bằng chính model của Snipe-IT. Lệnh `inv:catalog-install` gọi installer. Trang chỉ mục chỉ đọc config + `Category` và liên kết sang `categories.show` sẵn có.

**Tech Stack:** Laravel 12 (cấu trúc Laravel 10), Blade + AdminLTE 2/Bootstrap 3, PHPUnit, models `Category` / `CustomField` / `CustomFieldset` / `AssetModel` của Snipe-IT.

**Spec:** [`docs/superpowers/specs/2026-09-30-tich-hop-tac-tu-qlts-design.md`](../specs/2026-09-30-tich-hop-tac-tu-qlts-design.md) §18.1–18.3 (QĐ-15, QĐ-16). Kế hoạch tổng thể: [`2026-09-30-tich-hop-tac-tu-qlts-tong-the.md`](2026-09-30-tich-hop-tac-tu-qlts-tong-the.md).

## Global Constraints

Sao từ spec / kế hoạch tổng thể:

- **RB-1:** Không tự viết migration thêm/sửa cột bảng lõi Snipe-IT. Ngoại lệ duy nhất: cột `_snipeit_*` do Custom Fields chính hãng sinh ra. Mọi bảng mới dùng tiền tố `inv_` (GĐ8 **không** tạo bảng mới nào).
- **RB-2:** Chỉ được chạm 13 file lõi đã liệt kê ở spec §13. GĐ8 chỉ chạm **một** file lõi: `app/Providers/RouteServiceProvider.php` (#8, thêm đúng 1 dòng `require`). Chạm file lõi thứ 14 → dừng, xin duyệt.
- **RB-3:** Code mới nằm trong vùng riêng theo spec §14: `app/Services/Inventory/`, `app/Http/Controllers/Inventory/`, `app/Console/Commands/Inventory/`, `routes/web/inventory.php`, `resources/views/inventory/`, `resources/lang/*/admin/inventory/`, `tests/Feature/Inventory/`, `tests/Unit/Inventory/`.
- **RB-11:** Mọi nhãn giao diện phải có cả `resources/lang/vi-VN/` **và** `resources/lang/en-US/`.
- **RB-15 / QĐ-16:** **Không** sửa `resources/views/layouts/default.blade.php`. Menu kiểu GLPI = trang `/inventory/catalog` + liên kết sang `categories.show`.
- **RB-9 / QĐ-11:** Không tự tạo `locations`, không tự tạo tài sản. GĐ8 chỉ tạo danh mục/bộ trường/mô-đen mẫu.
- **Quy tắc dự án** (`.ai/rules`): test method snake_case; không thêm `RefreshDatabase`; không dùng `truncate()` trong test; mỗi route GET giao diện cần `test_page_renders` + `test_requires_permission`; mọi route giao diện có breadcrumb; migration không có khoá ngoại; PHP dùng ngoặc nhọn cho mọi cấu trúc điều khiển, kiểu trả về tường minh; chạy `vendor/bin/pint --dirty --format agent` trước khi commit file PHP.

## Điều đã kiểm chứng trong mã v8.7.2 (đừng làm lại, đừng đoán ngược)

| Điều | Bằng chứng | Hệ quả cho kế hoạch |
|---|---|---|
| Bộ trường gắn vào **mô-đen**, không phải danh mục | `AssetModel::$fillable` có `fieldset_id`; `AssetModel::fieldset()` (`AssetModel.php:283`) | Mỗi danh mục tài sản kèm 1 **mô-đen mẫu** mang bộ trường |
| Tạo `CustomField` chạy `ALTER TABLE assets ADD ... TEXT` (hook `created`) | `CustomField.php:~320-345` | Cột tên `_snipeit_<slug>_<id>`; test phải đọc `$field->db_column`, không hard-code |
| Tên trường phải **duy nhất toàn hệ thống** | `CustomField::$rules['name'] = 'required\|unique:custom_fields'` | Installer tìm theo tên và **dùng lại** nếu đã có |
| `format` nhận nhãn (`'NUMERIC'`, `'ANY'`) và tự đổi sang mẫu | `CustomField::setFormatAttribute` | Định nghĩa dùng nhãn `NUMERIC` / `ANY` |
| `checkbox`/`listbox`/`radio` **bắt buộc** `field_values` (xuống dòng ngăn cách) | `CustomField::getRules()` | Định nghĩa phải có `field_values` cho các phần tử đó |
| Không mã hoá được `checkbox`/`radio`/`DATE`/`DATETIME` | `CustomField::canEncryptFor()` | Trường bí mật (PIN/PUK) dùng `text` + `encrypted` |
| Nhập CSV khớp trường tùy chỉnh theo **tên cột = tên trường** (không phân biệt hoa/thường), không cần `column-mappings` | `Importer::populateCustomFields()` (`Importer.php:430-448`) | Test CSV chỉ mapping các cột chuẩn |
| `/hardware` **không** nhận `category_id` từ URL | `resources/views/hardware/index.blade.php:59-64` | Liên kết dùng `route('categories.show')` |
| `categories.show` đã liệt kê tài sản/mô-đen/vật tư/giấy phép của danh mục | `resources/views/categories/view.blade.php:47-81` | Không viết trang danh sách mới |
| Vật tư tiêu hao **chỉ cấp cho người dùng** (không cấp cho máy in/máy) | `ConsumableCheckoutController.php:107-124` (`assigned_user`) | Ghi rõ giới hạn ở phần nghiệm thu; không cố sửa |
| `Category::$fillable` gồm `name`, `category_type`; `created_by` **không** fillable | `Category.php:71-81` | Gán `created_by` bằng thuộc tính trực tiếp |
| `CustomFieldset` không có `created_by` trong factory | `CustomFieldsetFactory.php` | Không gán `created_by` cho bộ trường |
| `CustomFieldSeeder` của Snipe-IT gọi `CustomField::truncate()` và xoá mọi cột `_snipeit_*` | `database/seeders/CustomFieldSeeder.php:15-27` | **Cấm** gọi seeder này trên DB thật; installer của ta chỉ thêm, không xoá |
| `.env.testing` dùng **MySQL**; test tạo `CustomField` gọi `markIncompleteIfMySQL` (bị bỏ qua) và DDL phá transaction test | `.env.testing:34`; `tests/Support/CanSkipTests.php`; `.ai/rules/tests.md` | Chạy test DDL bằng SQLite trong bộ nhớ (xem "Quy ước lệnh") |

## Quy ước lệnh

PHP của dự án nằm ở thư mục gốc repo, **không** nằm trong worktree:

```bash
PHP="D:/DEV/Quanly-CNTT/tools/php/php.exe"
```

Test **không** tạo trường tùy chỉnh chạy được như bình thường:

```bash
$PHP vendor/bin/phpunit tests/Unit/Inventory/CatalogDefinitionTest.php
```

Test **có** tạo trường tùy chỉnh (DDL) phải chạy trên SQLite trong bộ nhớ, vì trên MySQL chúng bị bỏ qua:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: $PHP vendor/bin/phpunit tests/Feature/Inventory/CatalogInstallerTest.php
```

Điều kiện: `$PHP -m` phải liệt kê `pdo_sqlite`. Nếu **không** có, **dừng và báo** — kết quả "incomplete/skipped" **không** được tính là đạt (xem Task 6, Bước 3).

---

## Cấu trúc file

| File | Trách nhiệm |
|---|---|
| `config/inventory_catalog.php` (tạo) | Dữ liệu: kho trường tùy chỉnh + 16 mục danh mục. Không có logic |
| `app/Services/Inventory/Catalog/CatalogInstaller.php` (tạo) | Đọc định nghĩa, tạo idempotent Category/CustomField/CustomFieldset/AssetModel |
| `app/Console/Commands/Inventory/InstallCatalog.php` (tạo) | Lệnh `inv:catalog-install` |
| `app/Http/Controllers/Inventory/CatalogController.php` (tạo) | Trang chỉ mục |
| `routes/web/inventory.php` (tạo) | Route `inventory.catalog` + breadcrumb |
| `app/Providers/RouteServiceProvider.php` (sửa, **file lõi #8**) | 1 dòng `require` |
| `resources/views/inventory/catalog.blade.php` (tạo) | Bảng 16 mục |
| `resources/lang/vi-VN/admin/inventory/catalog.php`, `resources/lang/en-US/admin/inventory/catalog.php` (tạo) | Nhãn giao diện |
| `tests/Unit/Inventory/CatalogDefinitionTest.php` (tạo) | Kiểm cấu trúc định nghĩa (không DDL) |
| `tests/Feature/Inventory/CatalogInstallerTest.php` (tạo) | Installer + lệnh (có DDL) |
| `tests/Feature/Inventory/CatalogPageTest.php` (tạo) | Trang chỉ mục (không DDL) |
| `tests/Feature/Inventory/CatalogAssetFlowTest.php` (tạo) | Tạo thiết bị qua API và CSV bằng bộ trường đã cài (có DDL) |

---

### Task 1: Định nghĩa danh mục + bài kiểm cấu trúc

**Files:**
- Create: `config/inventory_catalog.php`
- Test: `tests/Unit/Inventory/CatalogDefinitionTest.php`

**Interfaces:**
- Consumes: `App\Models\CustomField::ELEMENT_KEYS`, `App\Models\CustomField::PREDEFINED_FORMATS`.
- Produces: `config('inventory_catalog')` với hai khoá:
  - `fields`: `array<string, array{name:string, element:string, format?:string, field_values?:string, encrypted?:bool, help?:string}>` — khoá ngắn → định nghĩa trường.
  - `entries`: `array<string, array{category:string, type:'asset'|'consumable'|'license', fields?:list<string>}>` — khoá mục → danh mục; `fields` là danh sách khoá ngắn (chỉ với `type=asset`).

- [ ] **Step 1: Viết bài kiểm cấu trúc (đỏ)**

Tạo `tests/Unit/Inventory/CatalogDefinitionTest.php`:

```php
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
```

- [ ] **Step 2: Chạy để thấy đỏ**

Run: `$PHP vendor/bin/phpunit tests/Unit/Inventory/CatalogDefinitionTest.php`
Expected: FAIL (`config('inventory_catalog.entries')` là `null` nên `assertCount(16, null)` lỗi).

- [ ] **Step 3: Viết định nghĩa**

Tạo `config/inventory_catalog.php`:

```php
<?php

/*
 * Danh mục thiết bị ngang menu GLPI - spec 2026-09-30 mục 18.
 *
 * 'fields'  : kho trường tùy chỉnh. Khoá ngắn => định nghĩa. Tên trường phải duy nhất toàn hệ thống.
 * 'entries' : 16 mục. Mỗi mục tài sản sinh 1 danh mục + 1 bộ trường + 1 mô-đen mẫu.
 *
 * KHÔNG khai ở đây các trường Snipe-IT đã có sẵn: tên, số sê-ri, asset tag (số hàng tồn kho),
 * nhà sản xuất, mô-đen, trạng thái, vị trí, người dùng, ghi chú (bình luận), danh mục (kiểu).
 * "Toàn cục" của GLPI = Tìm kiếm chung sẵn có của Snipe-IT nên không có mục ở đây.
 */

$tech = ['tech_user', 'tech_group'];
$contact = ['contact', 'contact_num'];

return [
    'fields' => [
        'tech_user' => ['name' => 'Kỹ thuật viên phụ trách', 'element' => 'text'],
        'tech_group' => ['name' => 'Nhóm phụ trách', 'element' => 'text'],
        'contact' => ['name' => 'Tên người dùng thay thế', 'element' => 'text'],
        'contact_num' => ['name' => 'Số người dùng thay thế', 'element' => 'text'],
        'groups' => ['name' => 'Nhóm', 'element' => 'text'],
        'network' => ['name' => 'Mạng', 'element' => 'text'],
        'brand' => ['name' => 'Thương hiệu', 'element' => 'text'],
        'sysdescr' => ['name' => 'Mô tả hệ thống (sysdescr)', 'element' => 'textarea'],
        'rack_parent' => ['name' => 'Rack chứa thiết bị', 'element' => 'text'],
        'rack_unit' => ['name' => 'Vị trí U trong rack', 'element' => 'text', 'format' => 'NUMERIC'],

        'monitor_size' => ['name' => 'Kích thước màn hình (inch)', 'element' => 'text', 'format' => 'NUMERIC'],
        'monitor_ports' => [
            'name' => 'Cổng và tính năng màn hình',
            'element' => 'checkbox',
            'field_values' => "Micro\nLoa\nD-sub\nBNC\nDVI\nPivot\nHDMI\nDisplayPort",
        ],

        'ne_ram' => ['name' => 'RAM thiết bị mạng (MB)', 'element' => 'text', 'format' => 'NUMERIC'],
        'ne_cpu' => ['name' => 'CPU thiết bị mạng', 'element' => 'text'],
        'ne_uptime' => ['name' => 'Thời gian hoạt động thiết bị mạng', 'element' => 'text'],

        'printer_ports' => [
            'name' => 'Cổng kết nối máy in',
            'element' => 'checkbox',
            'field_values' => "Serial\nParallel\nUSB\nWiFi\nEthernet",
        ],
        'printer_memory' => ['name' => 'Bộ nhớ máy in (MB)', 'element' => 'text', 'format' => 'NUMERIC'],
        'pages_init' => ['name' => 'Số trang in ban đầu', 'element' => 'text', 'format' => 'NUMERIC'],
        'pages_last' => ['name' => 'Số trang in gần nhất', 'element' => 'text', 'format' => 'NUMERIC'],

        'phone_line' => ['name' => 'Số đường dây', 'element' => 'text'],
        'phone_power' => ['name' => 'Nguồn cấp điện điện thoại', 'element' => 'text'],
        'phone_accessories' => [
            'name' => 'Phụ kiện điện thoại',
            'element' => 'checkbox',
            'field_values' => "Tai nghe\nLoa ngoài",
        ],

        'rack_width' => ['name' => 'Chiều rộng rack (mm)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_height' => ['name' => 'Chiều cao rack (mm)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_depth' => ['name' => 'Chiều sâu rack (mm)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_units' => ['name' => 'Số U của rack', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_room' => ['name' => 'Phòng máy', 'element' => 'text'],
        'rack_position' => ['name' => 'Vị trí trong phòng máy', 'element' => 'text'],
        'rack_orientation' => ['name' => 'Hướng đặt rack', 'element' => 'text'],
        'rack_color' => ['name' => 'Màu nền rack', 'element' => 'text'],
        'rack_max_power' => ['name' => 'Công suất tối đa rack (W)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_measured_power' => ['name' => 'Công suất đo được rack (W)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_max_weight' => ['name' => 'Tải trọng tối đa rack (kg)', 'element' => 'text', 'format' => 'NUMERIC'],

        'enc_orientation' => ['name' => 'Hướng đặt khung máy', 'element' => 'text'],
        'enc_power' => ['name' => 'Số nguồn cấp khung máy', 'element' => 'text', 'format' => 'NUMERIC'],

        'pdu_type' => ['name' => 'Loại PDU', 'element' => 'text'],

        'um_ip' => ['name' => 'Địa chỉ IP thiết bị', 'element' => 'text', 'format' => 'IP'],
        'um_hub' => ['name' => 'Là hub', 'element' => 'listbox', 'field_values' => "Có\nKhông"],

        'cable_type' => ['name' => 'Loại cáp', 'element' => 'text'],
        'cable_color' => ['name' => 'Màu cáp', 'element' => 'text'],
        'cable_end_a' => ['name' => 'Đầu cáp A (thiết bị và cổng)', 'element' => 'text'],
        'cable_end_b' => ['name' => 'Đầu cáp B (thiết bị và cổng)', 'element' => 'text'],
        'cable_strands' => ['name' => 'Số sợi cáp', 'element' => 'text', 'format' => 'NUMERIC'],

        'sim_pin' => ['name' => 'Mã PIN SIM', 'element' => 'text', 'encrypted' => true],
        'sim_pin2' => ['name' => 'Mã PIN 2 SIM', 'element' => 'text', 'encrypted' => true],
        'sim_puk' => ['name' => 'Mã PUK SIM', 'element' => 'text', 'encrypted' => true],
        'sim_puk2' => ['name' => 'Mã PUK 2 SIM', 'element' => 'text', 'encrypted' => true],
        'sim_msin' => ['name' => 'MSIN SIM', 'element' => 'text'],
        'sim_line' => ['name' => 'Số thuê bao', 'element' => 'text'],
        'sim_type' => ['name' => 'Loại thẻ SIM', 'element' => 'text'],
        'sim_voltage' => ['name' => 'Điện áp SIM', 'element' => 'text'],
        'sim_voip' => ['name' => 'Cho phép VoIP', 'element' => 'listbox', 'field_values' => "Có\nKhông"],
    ],

    'entries' => [
        'computers' => [
            'category' => 'Máy tính', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'network'],
        ],
        'monitors' => [
            'category' => 'Màn hình', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'monitor_size', 'monitor_ports'],
        ],
        'software' => ['category' => 'Phần mềm', 'type' => 'license'],
        'network_equipment' => [
            'category' => 'Thiết bị mạng', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'network', 'ne_ram', 'ne_cpu', 'ne_uptime', 'sysdescr'],
        ],
        'peripherals' => [
            'category' => 'Thiết bị ngoại vi', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'brand'],
        ],
        'printers' => [
            'category' => 'Máy in', 'type' => 'asset',
            'fields' => [
                ...$tech, ...$contact, 'groups', 'network',
                'printer_ports', 'printer_memory', 'pages_init', 'pages_last', 'sysdescr',
            ],
        ],
        'cartridges' => ['category' => 'Hộp mực', 'type' => 'consumable'],
        'consumables' => ['category' => 'Hàng tiêu dùng', 'type' => 'consumable'],
        'phones' => [
            'category' => 'Điện thoại', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'brand', 'phone_line', 'phone_power', 'phone_accessories'],
        ],
        'racks' => [
            'category' => 'Tủ rack', 'type' => 'asset',
            'fields' => [
                ...$tech, 'rack_width', 'rack_height', 'rack_depth', 'rack_units', 'rack_room',
                'rack_position', 'rack_orientation', 'rack_color', 'rack_max_power',
                'rack_measured_power', 'rack_max_weight',
            ],
        ],
        'enclosures' => [
            'category' => 'Khung máy', 'type' => 'asset',
            'fields' => [...$tech, 'rack_parent', 'rack_unit', 'enc_orientation', 'enc_power'],
        ],
        'pdus' => [
            'category' => 'Bộ phân phối nguồn (PDU)', 'type' => 'asset',
            'fields' => [...$tech, 'rack_parent', 'rack_unit', 'pdu_type'],
        ],
        'passive_equipment' => [
            'category' => 'Thiết bị thụ động', 'type' => 'asset',
            'fields' => [...$tech, 'rack_parent', 'rack_unit'],
        ],
        'unmanaged' => [
            'category' => 'Nội dung không được quản lý', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'network', 'um_ip', 'um_hub', 'sysdescr'],
        ],
        'cables' => [
            'category' => 'Cáp kết nối', 'type' => 'asset',
            'fields' => [...$tech, 'cable_type', 'cable_color', 'cable_end_a', 'cable_end_b', 'cable_strands'],
        ],
        'simcards' => [
            'category' => 'Thẻ SIM', 'type' => 'asset',
            'fields' => [
                ...$tech, 'sim_pin', 'sim_pin2', 'sim_puk', 'sim_puk2',
                'sim_msin', 'sim_line', 'sim_type', 'sim_voltage', 'sim_voip',
            ],
        ],
    ],
];
```

- [ ] **Step 4: Chạy để thấy xanh**

Run: `$PHP vendor/bin/phpunit tests/Unit/Inventory/CatalogDefinitionTest.php`
Expected: `OK (8 tests, ...)`. Nếu `test_every_field_in_the_pool_is_used_by_at_least_one_entry` đỏ, xoá trường thừa khỏi kho hoặc gán vào một mục — **không** nới lỏng bài kiểm.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add config/inventory_catalog.php tests/Unit/Inventory/CatalogDefinitionTest.php
git commit -m "feat(inventory): định nghĩa 16 mục danh mục thiết bị ngang menu GLPI (GĐ8)"
```

---

### Task 2: `CatalogInstaller` (idempotent)

**Files:**
- Create: `app/Services/Inventory/Catalog/CatalogInstaller.php`
- Test: `tests/Feature/Inventory/CatalogInstallerTest.php`

**Interfaces:**
- Consumes: định dạng `config('inventory_catalog')` (Task 1).
- Produces:
  - `App\Services\Inventory\Catalog\CatalogInstaller::__construct(array $definition)`
  - `CatalogInstaller::install(\App\Models\User $creator): array{categories:int, fields:int, fieldsets:int, models:int}` — số bản ghi **tạo mới** (chạy lại lần 2 trả toàn 0). Ném `\RuntimeException` nếu một bản ghi không qua validate.
  - `const FIELDSET_PREFIX = 'Bộ trường '`, `const MODEL_PREFIX = 'Mô-đen mẫu '` — tên bộ trường = tiền tố + tên danh mục; tên mô-đen mẫu = tiền tố + tên danh mục.

- [ ] **Step 1: Viết test (đỏ)**

Tạo `tests/Feature/Inventory/CatalogInstallerTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use App\Models\AssetModel;
use App\Models\Category;
use App\Models\CustomField;
use App\Models\CustomFieldset;
use App\Models\User;
use App\Services\Inventory\Catalog\CatalogInstaller;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

// Chạy bằng SQLite: tạo CustomField chạy ALTER TABLE assets (xem plan, "Quy ước lệnh").
class CatalogInstallerTest extends TestCase
{
    private function definition(): array
    {
        return [
            'fields' => [
                'tech' => ['name' => 'Kỹ thuật viên thử', 'element' => 'text'],
                'ports' => ['name' => 'Cổng thử', 'element' => 'checkbox', 'field_values' => "USB\nWiFi"],
                'pin' => ['name' => 'Mã PIN thử', 'element' => 'text', 'encrypted' => true],
            ],
            'entries' => [
                'printers' => ['category' => 'Máy in thử', 'type' => 'asset', 'fields' => ['tech', 'ports', 'pin']],
                'cartridges' => ['category' => 'Hộp mực thử', 'type' => 'consumable'],
            ],
        ];
    }

    public function test_install_creates_category_fieldset_fields_and_sample_model(): void
    {
        $this->markIncompleteIfMySQL('Custom Fields tests do not work on MySQL');

        $result = (new CatalogInstaller($this->definition()))->install(User::factory()->superuser()->create());

        $this->assertSame(['categories' => 2, 'fields' => 3, 'fieldsets' => 1, 'models' => 1], $result);

        $category = Category::where('name', 'Máy in thử')->where('category_type', 'asset')->firstOrFail();
        $fieldset = CustomFieldset::where('name', CatalogInstaller::FIELDSET_PREFIX.'Máy in thử')->firstOrFail();
        $model = AssetModel::where('name', CatalogInstaller::MODEL_PREFIX.'Máy in thử')->firstOrFail();

        $this->assertSame($category->id, $model->category_id);
        $this->assertSame($fieldset->id, $model->fieldset_id);
        $this->assertCount(3, $fieldset->fields);

        foreach ($fieldset->fields as $field) {
            $this->assertTrue(Schema::hasColumn('assets', $field->db_column), "Thiếu cột {$field->db_column} trên bảng assets");
        }

        $this->assertTrue(CustomField::where('name', 'Mã PIN thử')->firstOrFail()->field_encrypted == 1);
    }

    public function test_consumable_entry_creates_only_a_category(): void
    {
        $this->markIncompleteIfMySQL('Custom Fields tests do not work on MySQL');

        (new CatalogInstaller($this->definition()))->install(User::factory()->superuser()->create());

        $this->assertDatabaseHas('categories', ['name' => 'Hộp mực thử', 'category_type' => 'consumable']);
        $this->assertDatabaseMissing('custom_fieldsets', ['name' => CatalogInstaller::FIELDSET_PREFIX.'Hộp mực thử']);
        $this->assertDatabaseMissing('models', ['name' => CatalogInstaller::MODEL_PREFIX.'Hộp mực thử']);
    }

    public function test_installing_twice_creates_nothing_the_second_time(): void
    {
        $this->markIncompleteIfMySQL('Custom Fields tests do not work on MySQL');

        $installer = new CatalogInstaller($this->definition());
        $admin = User::factory()->superuser()->create();
        $installer->install($admin);

        $second = $installer->install($admin);

        $this->assertSame(['categories' => 0, 'fields' => 0, 'fieldsets' => 0, 'models' => 0], $second);
        $this->assertSame(3, CustomField::whereIn('name', ['Kỹ thuật viên thử', 'Cổng thử', 'Mã PIN thử'])->count());
        $this->assertCount(3, CustomFieldset::where('name', CatalogInstaller::FIELDSET_PREFIX.'Máy in thử')->firstOrFail()->fields);
    }

    public function test_a_custom_field_that_already_exists_by_name_is_reused(): void
    {
        $this->markIncompleteIfMySQL('Custom Fields tests do not work on MySQL');

        $existing = CustomField::factory()->create(['name' => 'Kỹ thuật viên thử']);

        $result = (new CatalogInstaller($this->definition()))->install(User::factory()->superuser()->create());

        $this->assertSame(2, $result['fields']);
        $fieldset = CustomFieldset::where('name', CatalogInstaller::FIELDSET_PREFIX.'Máy in thử')->firstOrFail();
        $this->assertTrue($fieldset->fields->contains('id', $existing->id));
    }

    public function test_an_invalid_definition_throws_instead_of_silently_skipping(): void
    {
        $definition = $this->definition();
        $definition['fields']['tech']['element'] = 'khong-hop-le';

        $this->expectException(RuntimeException::class);

        (new CatalogInstaller($definition))->install(User::factory()->superuser()->create());
    }
}
```

- [ ] **Step 2: Chạy để thấy đỏ**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: $PHP vendor/bin/phpunit tests/Feature/Inventory/CatalogInstallerTest.php`
Expected: FAIL — `Class "App\Services\Inventory\Catalog\CatalogInstaller" not found`.

- [ ] **Step 3: Viết installer**

Tạo `app/Services/Inventory/Catalog/CatalogInstaller.php`:

```php
<?php

namespace App\Services\Inventory\Catalog;

use App\Models\AssetModel;
use App\Models\Category;
use App\Models\CustomField;
use App\Models\CustomFieldset;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Cài danh mục thiết bị ngang menu GLPI (spec 2026-09-30, mục 18).
 *
 * Idempotent và chỉ THÊM: tìm theo tên, có rồi thì dùng lại, không bao giờ xoá hay sửa
 * bản ghi sẵn có. Cố ý KHÔNG dùng CustomFieldSeeder của Snipe-IT (nó truncate).
 */
class CatalogInstaller
{
    public const FIELDSET_PREFIX = 'Bộ trường ';

    public const MODEL_PREFIX = 'Mô-đen mẫu ';

    /**
     * @param  array{fields: array<string, array<string, mixed>>, entries: array<string, array<string, mixed>>}  $definition
     */
    public function __construct(private array $definition) {}

    /**
     * @return array{categories: int, fields: int, fieldsets: int, models: int} số bản ghi TẠO MỚI
     */
    public function install(User $creator): array
    {
        $created = ['categories' => 0, 'fields' => 0, 'fieldsets' => 0, 'models' => 0];

        foreach ($this->definition['entries'] as $entry) {
            $category = $this->category($entry, $creator, $created);

            if ($entry['type'] !== 'asset' || empty($entry['fields'])) {
                continue;
            }

            $fieldset = $this->fieldset(self::FIELDSET_PREFIX.$entry['category'], $created);

            foreach (array_values($entry['fields']) as $order => $fieldKey) {
                $field = $this->field($this->definition['fields'][$fieldKey], $creator, $created);
                $fieldset->fields()->syncWithoutDetaching([$field->id => ['required' => 0, 'order' => $order + 1]]);
            }

            $this->sampleModel(self::MODEL_PREFIX.$entry['category'], $category, $fieldset, $creator, $created);
        }

        return $created;
    }

    private function category(array $entry, User $creator, array &$created): Category
    {
        $category = Category::firstOrNew(['name' => $entry['category'], 'category_type' => $entry['type']]);

        if (! $category->exists) {
            $category->created_by = $creator->id;
            $this->save($category);
            $created['categories']++;
        }

        return $category;
    }

    private function fieldset(string $name, array &$created): CustomFieldset
    {
        $fieldset = CustomFieldset::firstOrNew(['name' => $name]);

        if (! $fieldset->exists) {
            $this->save($fieldset);
            $created['fieldsets']++;
        }

        return $fieldset;
    }

    private function field(array $definition, User $creator, array &$created): CustomField
    {
        $field = CustomField::firstOrNew(['name' => $definition['name']]);

        if (! $field->exists) {
            $field->fill([
                'element' => $definition['element'],
                'format' => $definition['format'] ?? 'ANY',
                'field_values' => $definition['field_values'] ?? null,
                'field_encrypted' => $definition['encrypted'] ?? false,
                'auto_add_to_fieldsets' => false,
            ]);
            $field->created_by = $creator->id;
            $this->save($field);
            $created['fields']++;
        }

        return $field;
    }

    private function sampleModel(string $name, Category $category, CustomFieldset $fieldset, User $creator, array &$created): void
    {
        $model = AssetModel::firstOrNew(['name' => $name, 'category_id' => $category->id]);

        if (! $model->exists) {
            $model->fieldset_id = $fieldset->id;
            $model->created_by = $creator->id;
            $this->save($model);
            $created['models']++;
        } elseif (empty($model->fieldset_id)) {
            $model->fieldset_id = $fieldset->id;
            $this->save($model);
        }
    }

    /**
     * Snipe-IT models validate via watson/validating: save() returns false instead of throwing.
     */
    private function save(Model $model): void
    {
        if (! $model->save()) {
            throw new RuntimeException(class_basename($model).': '.$model->getErrors()->first());
        }
    }
}
```

- [ ] **Step 4: Chạy để thấy xanh**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: $PHP vendor/bin/phpunit tests/Feature/Inventory/CatalogInstallerTest.php`
Expected: `OK (5 tests, ...)`.

Nếu một test đỏ vì thuộc tính không tồn tại (ví dụ cột `created_by` của `custom_fields`, `models` hoặc `categories`), **kiểm bằng `Schema::hasColumn` rồi bỏ đúng dòng gán `created_by` đó** — đừng đoán. Nếu `test_a_custom_field_that_already_exists_by_name_is_reused` đỏ vì `CustomField::factory()->create()` cần tham số khác, đọc `database/factories/CustomFieldFactory.php` và chỉnh **test**, không chỉnh installer.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Inventory/Catalog/CatalogInstaller.php tests/Feature/Inventory/CatalogInstallerTest.php
git commit -m "feat(inventory): CatalogInstaller cài danh mục/bộ trường/mô-đen mẫu idempotent (GĐ8)"
```

---

### Task 3: Lệnh `inv:catalog-install`

**Files:**
- Create: `app/Console/Commands/Inventory/InstallCatalog.php`
- Modify (test): `tests/Feature/Inventory/CatalogInstallerTest.php` (thêm 2 test cuối lớp)

**Interfaces:**
- Consumes: `CatalogInstaller::install(User): array` (Task 2); `config('inventory_catalog')`.
- Produces: lệnh `php artisan inv:catalog-install {--user=}`; exit code 0 khi cài xong, 1 khi không có người dùng để ghi làm người tạo.
- Lệnh được nạp tự động: `app/Console/Kernel.php:34-37` gọi `$this->load(__DIR__.'/Commands')` (đệ quy), nên **không** sửa Kernel.

- [ ] **Step 1: Viết test (đỏ)** — thêm hai test vào cuối lớp `CatalogInstallerTest`:

```php
    public function test_command_fails_without_a_superuser(): void
    {
        $this->artisan('inv:catalog-install')
            ->expectsOutputToContain('superuser')
            ->assertExitCode(1);
    }

    public function test_command_installs_and_reports_counts(): void
    {
        $this->markIncompleteIfMySQL('Custom Fields tests do not work on MySQL');

        config(['inventory_catalog' => $this->definition()]);
        User::factory()->superuser()->create();

        $this->artisan('inv:catalog-install')
            ->expectsOutputToContain('categories: 2')
            ->expectsOutputToContain('fields: 3')
            ->assertExitCode(0);

        $this->assertDatabaseHas('categories', ['name' => 'Máy in thử']);
    }
```

- [ ] **Step 2: Chạy để thấy đỏ**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: $PHP vendor/bin/phpunit tests/Feature/Inventory/CatalogInstallerTest.php --filter=command`
Expected: FAIL — `The command "inv:catalog-install" does not exist.`

- [ ] **Step 3: Viết lệnh**

Tạo `app/Console/Commands/Inventory/InstallCatalog.php`:

```php
<?php

namespace App\Console\Commands\Inventory;

use App\Models\User;
use App\Services\Inventory\Catalog\CatalogInstaller;
use Illuminate\Console\Command;

class InstallCatalog extends Command
{
    protected $signature = 'inv:catalog-install {--user= : ID người dùng ghi là người tạo (mặc định: superuser đầu tiên)}';

    protected $description = 'Cài danh mục thiết bị ngang menu GLPI (spec mục 18) - chạy lại an toàn, chỉ thêm không xoá';

    public function handle(): int
    {
        $creator = $this->option('user')
            ? User::find($this->option('user'))
            : User::where('permissions->superuser', '1')->first();

        if (! $creator) {
            $this->error('Không tìm thấy người dùng để ghi làm người tạo. Tạo một superuser hoặc truyền --user=ID.');

            return self::FAILURE;
        }

        $result = (new CatalogInstaller(config('inventory_catalog')))->install($creator);

        foreach ($result as $what => $count) {
            $this->line("{$what}: {$count}");
        }
        $this->info('Xong. Chạy lại lệnh này không tạo trùng.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Chạy để thấy xanh**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: $PHP vendor/bin/phpunit tests/Feature/Inventory/CatalogInstallerTest.php`
Expected: `OK (7 tests, ...)`.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Console/Commands/Inventory/InstallCatalog.php tests/Feature/Inventory/CatalogInstallerTest.php
git commit -m "feat(inventory): lệnh inv:catalog-install (GĐ8)"
```

---

### Task 4: Trang chỉ mục `/inventory/catalog`

**Files:**
- Create: `app/Http/Controllers/Inventory/CatalogController.php`
- Create: `routes/web/inventory.php`
- Create: `resources/views/inventory/catalog.blade.php`
- Create: `resources/lang/vi-VN/admin/inventory/catalog.php`
- Create: `resources/lang/en-US/admin/inventory/catalog.php`
- Modify: `app/Providers/RouteServiceProvider.php` (file lõi #8 — thêm **đúng 1 dòng**)
- Test: `tests/Feature/Inventory/CatalogPageTest.php`

**Interfaces:**
- Consumes: `config('inventory_catalog.entries')`; `Category`; route sẵn có `categories.show`.
- Produces: route tên `inventory.catalog` (`GET /inventory/catalog`); quyền: `authorize('view', Category::class)`.

- [ ] **Step 1: Viết test (đỏ)**

Tạo `tests/Feature/Inventory/CatalogPageTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use App\Models\Category;
use App\Models\User;
use Tests\TestCase;

class CatalogPageTest extends TestCase
{
    private function useMiniCatalog(): void
    {
        config(['inventory_catalog.entries' => [
            'printers' => ['category' => 'Máy in thử', 'type' => 'asset'],
            'phones' => ['category' => 'Điện thoại thử', 'type' => 'asset'],
        ]]);
    }

    public function test_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('inventory.catalog'))
            ->assertForbidden();
    }

    public function test_page_renders(): void
    {
        $this->useMiniCatalog();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.catalog'))
            ->assertOk()
            ->assertSee('Máy in thử')
            ->assertSee('Điện thoại thử');
    }

    public function test_installed_category_links_to_the_category_page_and_shows_its_count(): void
    {
        $this->useMiniCatalog();
        $category = Category::factory()->create(['name' => 'Máy in thử', 'category_type' => 'asset']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.catalog'))
            ->assertOk()
            ->assertSee(route('categories.show', $category->id), false);
    }

    public function test_entry_that_is_not_installed_yet_says_so_instead_of_linking(): void
    {
        $this->useMiniCatalog();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.catalog'))
            ->assertOk()
            ->assertSee(trans('admin/inventory/catalog.not_installed'));
    }
}
```

- [ ] **Step 2: Chạy để thấy đỏ**

Run: `$PHP vendor/bin/phpunit tests/Feature/Inventory/CatalogPageTest.php`
Expected: FAIL — `Route [inventory.catalog] not defined.`

- [ ] **Step 3: Viết route, controller, view, lang, và nạp route**

`routes/web/inventory.php`:

```php
<?php

use App\Http\Controllers\Inventory\CatalogController;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

Route::group(['prefix' => 'inventory', 'middleware' => ['auth']], function () {
    Route::get('catalog', [CatalogController::class, 'index'])
        ->name('inventory.catalog')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('home')
            ->push(trans('admin/inventory/catalog.title'), route('inventory.catalog'))
        );
});
```

`app/Http/Controllers/Inventory/CatalogController.php`:

```php
<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Trang chỉ mục kiểu menu GLPI: mỗi mục liên kết tới trang danh mục sẵn có của Snipe-IT.
     */
    public function index(): View
    {
        $this->authorize('view', Category::class);

        $entries = collect(config('inventory_catalog.entries'))
            ->map(function (array $entry, string $key): array {
                $category = Category::where('name', $entry['category'])
                    ->where('category_type', $entry['type'])
                    ->first();

                return [
                    'key' => $key,
                    'name' => $entry['category'],
                    'type' => $entry['type'],
                    'category' => $category,
                    'count' => $category ? $category->itemCount() : 0,
                ];
            });

        return view('inventory.catalog', ['entries' => $this->asList($entries)]);
    }

    private function asList(Collection $entries): Collection
    {
        return $entries->values();
    }
}
```

`resources/views/inventory/catalog.blade.php`:

```blade
@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/inventory/catalog.title') }}
@parent
@stop

{{-- Page content --}}
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                <p class="text-muted">{{ trans('admin/inventory/catalog.intro') }}</p>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/inventory/catalog.column_name') }}</th>
                            <th>{{ trans('admin/inventory/catalog.column_type') }}</th>
                            <th class="text-right">{{ trans('admin/inventory/catalog.column_count') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td>
                                    @if ($entry['category'])
                                        <a href="{{ route('categories.show', $entry['category']->id) }}">{{ $entry['name'] }}</a>
                                    @else
                                        {{ $entry['name'] }}
                                        <span class="text-muted">— {{ trans('admin/inventory/catalog.not_installed') }}</span>
                                    @endif
                                </td>
                                <td>{{ trans('admin/inventory/catalog.type_'.$entry['type']) }}</td>
                                <td class="text-right">{{ $entry['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
```

`resources/lang/vi-VN/admin/inventory/catalog.php`:

```php
<?php

return [
    'title' => 'Danh mục thiết bị',
    'intro' => 'Các loại thiết bị và vật tư tương ứng menu Tài sản của GLPI. Bấm vào một mục để xem thiết bị, mô-đen hoặc vật tư của danh mục đó.',
    'column_name' => 'Danh mục',
    'column_type' => 'Loại',
    'column_count' => 'Số lượng',
    'not_installed' => 'chưa cài (chạy: php artisan inv:catalog-install)',
    'type_asset' => 'Tài sản',
    'type_consumable' => 'Vật tư tiêu hao',
    'type_license' => 'Giấy phép phần mềm',
];
```

`resources/lang/en-US/admin/inventory/catalog.php`:

```php
<?php

return [
    'title' => 'Device catalog',
    'intro' => 'Device and supply types matching the GLPI Assets menu. Click an entry to see the devices, models or supplies in that category.',
    'column_name' => 'Category',
    'column_type' => 'Type',
    'column_count' => 'Count',
    'not_installed' => 'not installed (run: php artisan inv:catalog-install)',
    'type_asset' => 'Asset',
    'type_consumable' => 'Consumable',
    'type_license' => 'Software license',
];
```

Nạp route — trong `app/Providers/RouteServiceProvider.php`, thêm **một dòng** ngay sau dòng `require base_path('routes/web/kits.php');`:

```php
            require base_path('routes/web/inventory.php');
```

- [ ] **Step 4: Chạy để thấy xanh**

Run: `$PHP vendor/bin/phpunit tests/Feature/Inventory/CatalogPageTest.php`
Expected: `OK (4 tests, ...)`.

Nếu `assertForbidden` nhận `302`/`200`: đọc `tests/Feature/Categories/Ui/IndexCategoriesTest.php` (cùng kiểu kiểm) và bảo đảm route đi qua middleware `auth` như trên; **không** bỏ `authorize`.

- [ ] **Step 5: Kiểm chứng "chỉ 1 dòng lõi" và commit**

Run: `git diff --stat app/Providers/RouteServiceProvider.php`
Expected: phần thay đổi của **task này** là `1 insertion(+)` cho dòng `require ... inventory.php`. (Worktree này còn thay đổi `mapAgentRoutes` của GĐ1 chưa commit trong cùng file — nếu có, dùng `git add -p` chỉ đưa dòng `inventory.php` vào commit này, hoặc chờ GĐ1 commit trước.)

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Inventory/CatalogController.php routes/web/inventory.php \
  resources/views/inventory/catalog.blade.php \
  resources/lang/vi-VN/admin/inventory/catalog.php resources/lang/en-US/admin/inventory/catalog.php \
  tests/Feature/Inventory/CatalogPageTest.php
git add -p app/Providers/RouteServiceProvider.php   # chỉ chọn dòng require inventory.php
git commit -m "feat(inventory): trang chỉ mục /inventory/catalog kiểu menu GLPI (GĐ8)"
```

---

### Task 5: Tạo thiết bị bằng bộ trường đã cài — qua API và CSV

**Files:**
- Test: `tests/Feature/Inventory/CatalogAssetFlowTest.php`

**Interfaces:**
- Consumes: `CatalogInstaller` (Task 2). Route `api.assets.store`, `api.imports.store`, `api.imports.importFile` (sẵn có).
- Produces: bằng chứng cho cổng kiểm soát GĐ8: "tạo được 1 thiết bị mỗi loại qua giao diện **và** CSV". Không có code sản xuất mới.

Bài này **không** tạo file mã nguồn; nếu nó đỏ, lỗi nằm ở Task 1–2 (định nghĩa hoặc installer), sửa ở đó.

- [ ] **Step 1: Viết test**

Tạo `tests/Feature/Inventory/CatalogAssetFlowTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\CustomField;
use App\Models\Import;
use App\Models\Statuslabel;
use App\Models\User;
use App\Services\Inventory\Catalog\CatalogInstaller;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

// Chạy bằng SQLite: tạo CustomField chạy ALTER TABLE assets (xem plan, "Quy ước lệnh").
class CatalogAssetFlowTest extends TestCase
{
    private function installMiniCatalog(): AssetModel
    {
        (new CatalogInstaller([
            'fields' => [
                'tech' => ['name' => 'Kỹ thuật viên thử', 'element' => 'text'],
                'pin' => ['name' => 'Mã PIN thử', 'element' => 'text', 'encrypted' => true],
            ],
            'entries' => [
                'sims' => ['category' => 'Thẻ SIM thử', 'type' => 'asset', 'fields' => ['tech', 'pin']],
            ],
        ]))->install(User::factory()->superuser()->create());

        return AssetModel::where('name', CatalogInstaller::MODEL_PREFIX.'Thẻ SIM thử')->firstOrFail();
    }

    private function uploaded(string $filename, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $content);

        return new UploadedFile($path, $filename, 'text/csv', null, true);
    }

    public function test_a_device_can_be_created_through_the_api_with_catalog_fields(): void
    {
        $this->markIncompleteIfMySQL('Custom Fields tests do not work on MySQL');

        $model = $this->installMiniCatalog();
        $tech = CustomField::where('name', 'Kỹ thuật viên thử')->firstOrFail();
        $pin = CustomField::where('name', 'Mã PIN thử')->firstOrFail();
        $status = Statuslabel::factory()->readyToDeploy()->create();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => 'SIM-0001',
                'model_id' => $model->id,
                'status_id' => $status->id,
                $tech->db_column_name() => 'Nguyễn Văn A',
                $pin->db_column_name() => '1234',
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        $asset = Asset::findOrFail($response['payload']['id']);
        $this->assertSame('Nguyễn Văn A', $asset->{$tech->db_column_name()});
        $this->assertSame('1234', Crypt::decrypt($asset->{$pin->db_column_name()}));
    }

    public function test_a_device_can_be_imported_from_csv_using_the_field_name_as_the_column_header(): void
    {
        $this->markIncompleteIfMySQL('Custom Fields tests do not work on MySQL');

        $model = $this->installMiniCatalog();
        $tech = CustomField::where('name', 'Kỹ thuật viên thử')->firstOrFail();
        $status = Statuslabel::factory()->create();
        $importer = User::factory()->canImport()->create();

        $csv = "asset tag,item name,category,status,model name,Kỹ thuật viên thử\n";
        $csv .= "SIM-CSV-01,SIM nhập CSV,{$model->category->name},{$status->name},{$model->name},Trần Thị B\n";

        $this->actingAsForApi($importer)
            ->postJson(route('api.imports.store'), [
                'files' => [$this->uploaded('sim.csv', $csv)],
            ])
            ->assertSuccessful();

        $import = Import::latest()->first();

        $this->actingAsForApi($importer)
            ->postJson(route('api.imports.importFile', $import->id), [
                'import-type' => 'asset',
                'column-mappings' => [
                    'asset tag' => 'asset_tag',
                    'item name' => 'item_name',
                    'category' => 'category',
                    'status' => 'status',
                    'model name' => 'asset_model',
                ],
            ])
            ->assertSuccessful();

        $asset = Asset::where('asset_tag', 'SIM-CSV-01')->firstOrFail();
        $this->assertSame('Trần Thị B', $asset->{$tech->db_column_name()});
    }
}
```

- [ ] **Step 2: Chạy**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: $PHP vendor/bin/phpunit tests/Feature/Inventory/CatalogAssetFlowTest.php`
Expected: `OK (2 tests, ...)`.

Nếu bài CSV đỏ vì `Location`/`Company` bắt buộc, dựng thêm theo đúng mẫu `tests/Feature/Importing/AssetImportCreatedByTest.php:34-40` (`Location::factory()->create(); Company::factory()->create();`) — đó là điều kiện của bộ nhập, không phải lỗi của GĐ8.

- [ ] **Step 3: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Inventory/CatalogAssetFlowTest.php
git commit -m "test(inventory): tạo thiết bị bằng bộ trường danh mục qua API và CSV (GĐ8)"
```

---

### Task 6: Cài thật lên bản sao DB, ghi tài liệu, kiểm chứng cổng GĐ8

**Files:**
- Modify: `docs/superpowers/plans/2026-09-30-tich-hop-tac-tu-qlts-tong-the.md` (dòng GĐ8 trong bảng lộ trình: trỏ tới file này)
- Modify: `docs/superpowers/specs/2026-09-30-tich-hop-tac-tu-qlts-design.md` (§18.4/18.5 không đổi; ghi chú giới hạn vật tư ở §18.1)

**Interfaces:**
- Consumes: mọi Task trước.
- Produces: bằng chứng chạy thật cho cổng kiểm soát GĐ8.

- [ ] **Step 1: Chạy toàn bộ test của GĐ8**

```bash
$PHP vendor/bin/phpunit tests/Unit/Inventory/CatalogDefinitionTest.php tests/Feature/Inventory/CatalogPageTest.php
DB_CONNECTION=sqlite DB_DATABASE=:memory: $PHP vendor/bin/phpunit tests/Feature/Inventory/CatalogInstallerTest.php tests/Feature/Inventory/CatalogAssetFlowTest.php
```
Expected: cả hai lệnh `OK`, **không** có dòng `Incomplete` / `Skipped`. Dán kết quả ra.

- [ ] **Step 2: Chạy lại toàn bộ test Inventory để chắc không làm hỏng GĐ1**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: $PHP vendor/bin/phpunit tests/Feature/Inventory tests/Unit/Inventory`
Expected: `OK`.

- [ ] **Step 3: Cài thật lên MỘT BẢN SAO của CSDL Snipe-IT (không phải bản đang dùng)**

Vì test SQLite không chứng minh được `ALTER TABLE` trên MySQL thật, phải chạy 1 lần trên MySQL:

1. Sao lưu/nhân bản CSDL dev sang schema mới `snipeit_gd8_check` (mysqldump → import), trỏ `.env` tạm sang schema đó.
2. Chạy: `$PHP artisan inv:catalog-install`
   Expected (trên DB chưa có danh mục/trường trùng tên): lệnh thoát mã 0, in `categories: 16`, `fields: 52` (số khoá trong `inventory_catalog.fields`), `fieldsets: 13`, `models: 13` (13 mục tài sản, trừ Phần mềm / Hộp mực / Hàng tiêu dùng), không có lỗi `Duplicate column` / `Row size too large`. Nếu DB đã có sẵn bản ghi trùng tên thì các số nhỏ hơn — đó là đúng, vì installer dùng lại bản ghi có sẵn. Đối chiếu số kỳ vọng bằng `$PHP artisan tinker --execute 'echo count(config("inventory_catalog.fields"));'` trước khi kết luận.
3. Chạy lần 2: kỳ vọng mọi số về `0`.
4. Mở `/inventory/catalog` bằng tài khoản admin: đủ 16 dòng, không dòng nào còn "chưa cài", mỗi dòng bấm vào ra trang danh mục đúng.
5. Tạo tay 1 tài sản thuộc mô-đen "Mô-đen mẫu Máy in" trên giao diện web: form hiện đủ các trường của bộ "Bộ trường Máy in", lưu được.
6. Khôi phục `.env` về DB cũ; xoá schema tạm.

**Nếu `$PHP -m` không có `pdo_sqlite` (nên Step 1 bị "Incomplete")**: Step 3 trở thành bằng chứng duy nhất cho phần DDL — làm đủ, và **báo rõ** với chủ đầu tư rằng test tự động phần installer chưa chạy được.

**Nếu gặp `Row size too large` (MySQL giới hạn 65.535 byte/dòng)**: dừng. 52 cột `TEXT` thường không vượt vì TEXT lưu ngoài dòng, nhưng phải kiểm thật; nếu vượt, báo lại để bàn (giảm trường hoặc gộp), **không** tự đổi kiểu cột lõi.

- [ ] **Step 4: Kiểm chứng cảnh báo tồn kho tối thiểu của vật tư tiêu hao**

Run: `grep -rn "min_amt" app/Console/Commands app/Notifications app/Mail | head`
Expected: có ít nhất một chỗ (lệnh gửi cảnh báo tồn kho thấp) dùng `min_amt` của `Consumable`. Ghi kết quả vào phần "Ghi chú kiểm chứng" cuối file này. Nếu **không** có: báo lại — tiêu chí "hộp mực có cảnh báo tồn kho tối thiểu" sẽ chỉ còn là trường `min_amt` (nhập được, hiện cột "còn thiếu" trong danh sách), không có email.

- [ ] **Step 5: Ghi tài liệu**

Trong kế hoạch tổng thể, ở dòng GĐ8, đổi `*viết khi tới lượt*` thành liên kết `[2026-09-30-gd8-danh-muc-thiet-bi.md](2026-09-30-gd8-danh-muc-thiet-bi.md)`.

Trong spec §18.1, ngay dưới bảng ánh xạ, thêm đúng đoạn:

```markdown
> **Giới hạn đã kiểm chứng:** Vật tư tiêu hao của Snipe-IT chỉ cấp phát cho **người dùng** (`ConsumableCheckoutController` chỉ nhận `assigned_user`), không cấp cho máy in hay máy. Hộp mực vì thế theo dõi được tồn kho và người nhận, **không** theo dõi được "hộp mực nào đang lắp trong máy in nào" như GLPI.
```

- [ ] **Step 6: Commit**

```bash
git add docs/superpowers/plans/2026-09-30-tich-hop-tac-tu-qlts-tong-the.md docs/superpowers/specs/2026-09-30-tich-hop-tac-tu-qlts-design.md docs/superpowers/plans/2026-09-30-gd8-danh-muc-thiet-bi.md
git commit -m "docs(inventory): kế hoạch thực thi GĐ8 và giới hạn vật tư tiêu hao"
```

---

## Cổng kiểm soát GĐ8 (đối chiếu kế hoạch tổng thể)

| Yêu cầu | Bằng chứng |
|---|---|
| Đủ danh mục + bộ trường + mô-đen mẫu | Task 6 Bước 3 (chạy thật trên MySQL) + `CatalogInstallerTest` |
| Trang `/inventory/catalog` mở đúng | `CatalogPageTest` + Task 6 Bước 3.4 |
| Tạo được 1 thiết bị mỗi loại qua giao diện **và** CSV | `CatalogAssetFlowTest` (API + CSV) + Task 6 Bước 3.5 (giao diện web) |
| Hộp mực là vật tư tiêu hao có cảnh báo tồn kho tối thiểu | Task 1 (`type=consumable`) + Task 6 Bước 4 |
| Không chạm file lõi ngoài 13 file | Task 4 Bước 5: chỉ 1 dòng ở `RouteServiceProvider.php` |

## Ghi chú kiểm chứng

- **Cảnh báo tồn kho tối thiểu (Bước 4):** lệnh `grep -rn "min_amt" app/Console/Commands app/Notifications app/Mail` **không có kết quả nào**. `min_amt` không được đọc trực tiếp trong ba thư mục đó. Tuy vậy cảnh báo vẫn tồn tại qua đường gián tiếp: `app/Console/Commands/SendInventoryAlerts.php:46` gọi `Helper::checkLowInventory()`, và hàm này (`app/Helpers/Helper.php` khoảng dòng 869-872) truy vấn `Consumable` theo `qty` so với `min_amt` (`whereNotNull('min_amt')`). Ngoài ra `app/Http/Controllers/Api/LowStockController.php` và `app/Livewire/AlertMenu.php` cũng hiển thị mức thấp. Kết luận: hộp mực là vật tư tiêu hao có `min_amt` và được lệnh `SendInventoryAlerts` (email theo cài đặt cảnh báo) cùng menu cảnh báo bắt; chưa chạy thật lệnh gửi email trong GĐ8.
- **Cài danh mục thật trên MySQL (thay Bước 3 gốc):** bằng test tạm (không commit) trên DB test `snipeit_testing`: `CatalogInstaller` với `config('inventory_catalog')` thật cho `categories 16, fields 52, fieldsets 13, models 13`; cả 52 cột `db_column` có trong bảng `assets`; chạy lại ra toàn số 0; mỗi mô-đen mẫu có bộ trường đúng số trường. Không gặp `Row size too large` / `Duplicate column`. Bước 3.4-3.5 (mở giao diện bằng trình duyệt trên bản sao DB dev) chưa làm; `CatalogPageTest` và `CatalogAssetFlowTest` phủ phần tương ứng.

## Self-review

- **Phủ spec §18.1–18.3:** 16 mục → Task 1; bộ trường theo QĐ-15 → Task 1–2; menu QĐ-16 → Task 4; nhập tay/CSV → Task 5. UUID/ngày khởi động/khối agent **không** làm ở đây (đã ở `inv_hardware`, `inv_agents` — GĐ2). "Toàn cục" = Tìm kiếm sẵn có, không có việc.
- **Không có placeholder:** mọi bước mã đều có mã đầy đủ; các chỗ "nếu đỏ thì…" chỉ chỉ dẫn cách phân biệt lỗi test và lỗi mã, kèm nơi đọc.
- **Nhất quán kiểu:** `CatalogInstaller::install(User): array{categories,fields,fieldsets,models}`, hằng `FIELDSET_PREFIX`/`MODEL_PREFIX`, route `inventory.catalog`, lang `admin/inventory/catalog.*` dùng thống nhất ở Task 2–6.
- **Số liệu:** 52 trường và 13 mục tài sản là số tôi đếm tay từ `config/inventory_catalog.php` ở Task 1. Task 6 Bước 3 yêu cầu đếm lại bằng `count(config('inventory_catalog.fields'))`; nếu khác thì lấy số đếm được và sửa dòng kỳ vọng.
- **Chưa kiểm chứng được lúc viết kế hoạch:** (1) `pdo_sqlite` có trong `tools\php\php.exe` hay không; (2) cột `created_by` có trên `custom_fields`/`models`/`categories` hay không (factory có gán, nhưng chưa đọc migration) — Task 2 Bước 4 nêu cách xử lý; (3) phản hồi 403 của route mới (Task 4 Bước 4 nêu cách xử lý). Không có mục nào được giả định đúng mà giấu đi.
