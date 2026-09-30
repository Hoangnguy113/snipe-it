# GĐ1 — Đường ống tiếp nhận kiểm kê — Kế hoạch thực thi

> **Dành cho người thực thi:** BẮT BUỘC dùng sub-skill `superpowers:subagent-driven-development` (khuyến nghị) hoặc `superpowers:executing-plans` để làm từng việc một. Các bước dùng checkbox `- [ ]` để theo dõi.

**Mục tiêu:** Snipe-IT nhận được bản kiểm kê từ GLPI Agent 1.20, lưu lại, khớp với tài sản, và đọc được RustDesk ID — nền móng cho mọi giai đoạn sau.

**Kiến trúc:** Route riêng `routes/agent.php` **ngoài nhóm middleware `web`** (nên không có CSRF, không có session). Chứng thực bằng **HTTP Basic** — cơ chế duy nhất agent hỗ trợ. Giải nén zlib/gzip, nhận diện JSON vs XML, lưu nguyên bản nén vào `inv_snapshots`, đẩy việc phân tích sang queue.

**Công nghệ:** Laravel 12 · PHP 8.2.33 (`tools/php/php.exe`) · PHPUnit 11 · MySQL · zlib

**Spec:** [`../specs/2026-09-30-tich-hop-tac-tu-qlts-design.md`](../specs/2026-09-30-tich-hop-tac-tu-qlts-design.md)

**Kế hoạch tổng thể:** [`2026-09-30-tich-hop-tac-tu-qlts-tong-the.md`](2026-09-30-tich-hop-tac-tu-qlts-tong-the.md)

## Ràng buộc toàn dự án áp dụng cho GĐ1

| # | Ràng buộc |
|---|---|
| RB-1 | Không thêm/sửa cột nào trên bảng lõi. Bảng mới: `inv_agents`, `inv_snapshots`, `inv_unmatched` |
| RB-2 | **Chỉ được chạm 1 file lõi: `app/Providers/RouteServiceProvider.php`.** Chạm file lõi thứ 2 → dừng, xin duyệt |
| RB-4 | **Mọi lần đọc mục/trường phải qua `SectionReader`** (so khoá không phân biệt hoa/thường) — JSON gửi chữ thường, XML gửi chữ hoa |
| RB-8 | `AssetMatcher` **không bao giờ** khớp theo RustDesk ID |
| RB-9 | Không tự tạo tài sản mới — ghi `inv_unmatched` |
| RB-12 | Ngưỡng mặc định: kiểm kê **24 giờ**, deploy hỏi việc **4 giờ** |
| RB-13 | Chạy test: `./tools/php/php.exe vendor/bin/phpunit` |

## Sơ đồ file

| File | Trách nhiệm |
|---|---|
| `config/inventory.php` | **Tạo** — mã bí mật + 9 ngưỡng |
| `database/migrations/*_create_inv_agents_table.php` | **Tạo** — sổ đăng ký tác tử |
| `database/migrations/*_create_inv_snapshots_table.php` | **Tạo** — lưu bản kiểm kê thô |
| `database/migrations/*_create_inv_unmatched_table.php` | **Tạo** — máy chưa khớp tài sản |
| `app/Models/Inventory/InvAgent.php` | **Tạo** |
| `app/Models/Inventory/InvSnapshot.php` | **Tạo** |
| `app/Models/Inventory/InvUnmatched.php` | **Tạo** |
| `app/Services/Inventory/PayloadDecoder.php` | **Tạo** — giải nén + nhận diện giao thức |
| `app/Services/Inventory/DecodedPayload.php` | **Tạo** — đối tượng giá trị (giao thức + nội dung) |
| `app/Services/Inventory/SectionReader.php` | **Tạo** — **điểm thực thi RB-4** |
| `app/Services/Inventory/InvalidPayloadException.php` | **Tạo** |
| `app/Services/Inventory/ContactResponder.php` | **Tạo** — sinh CONTACT JSON |
| `app/Services/Inventory/AssetMatcher.php` | **Tạo** — khớp máy ↔ tài sản |
| `app/Http/Middleware/VerifyAgentRequest.php` | **Tạo** — Basic auth + kiểm UUID |
| `app/Http/Controllers/Agent/InventoryIngestController.php` | **Tạo** — phân nhánh contact / inventory |
| `app/Jobs/Inventory/ProcessSnapshot.php` | **Tạo** — phân tích trong queue |
| `app/Console/Commands/Inventory/ImportInventoryFile.php` | **Tạo** — `inv:import` |
| `app/Console/Commands/Inventory/InventoryDoctor.php` | **Tạo** — `inv:doctor` |
| `routes/agent.php` | **Tạo** |
| `app/Providers/RouteServiceProvider.php` | **Sửa** — thêm `mapAgentRoutes()` (**file lõi duy nhất**) |

---

## Task 1: Làm cho test chạy được (việc chặn)

> **Vì sao là việc đầu tiên:** đã kiểm — `vendor/phpunit` **không tồn tại**, dự án cài bằng `composer install --no-dev`. Hiện tại **không chạy được test nào**. Superpowers bắt buộc TDD nên phải xử lý trước.

**Files:**
- Create: `.env.testing` (nếu chưa có)

- [ ] **Bước 1: Xác nhận vấn đề**

```bash
cd /d/DEV/Quanly-CNTT && ls vendor/bin/ | grep -c phpunit
```

Kỳ vọng: `0` — xác nhận phpunit chưa có.

- [ ] **Bước 2: Cài dev dependencies**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe tools/composer.phar install
```

Kỳ vọng: cài xong, `ls vendor/bin/ | grep phpunit` trả về `phpunit`.

- [ ] **Bước 3: Tạo cơ sở dữ liệu riêng cho test**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe -r "
\$p = new PDO('mysql:host=127.0.0.1;port=3307', 'root', '');
\$p->exec('CREATE DATABASE IF NOT EXISTS snipeit_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo \"da tao snipeit_testing\n\";
"
```

> Nếu tài khoản/mật khẩu MySQL khác, đọc `.env` hiện tại để lấy đúng `DB_USERNAME` / `DB_PASSWORD` rồi sửa lệnh trên.

- [ ] **Bước 4: Tạo `.env.testing`**

```bash
cd /d/DEV/Quanly-CNTT && cp .env .env.testing && cat >> .env.testing <<'EOF'

# --- Ghi de cho moi truong test (dat sau nen thang gia tri o tren) ---
APP_ENV=testing
DB_DATABASE=snipeit_testing
QUEUE_CONNECTION=sync
CACHE_DRIVER=array
SESSION_DRIVER=array
MAIL_MAILER=array
INVENTORY_AGENT_USER=qlts-agent
INVENTORY_AGENT_SECRET=ma-bi-mat-dung-cho-test
EOF
echo "da tao .env.testing"
```

- [ ] **Bước 5: Chạy 1 test có sẵn để xác nhận hạ tầng test hoạt động**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=SendInventoryAlertsTest
```

Kỳ vọng: **PASS** (hoặc skip), **không** lỗi kết nối CSDL.
**Nếu lỗi:** dừng lại, dùng `superpowers:systematic-debugging`. Không đi tiếp Task 2.

- [ ] **Bước 6: Commit**

```bash
cd /d/DEV/Quanly-CNTT && git add .env.testing && git commit -m "chore: bo sung moi truong test (dev deps chua duoc cai truoc day)"
```

> `.env.testing` chứa mật khẩu CSDL cục bộ. Kiểm `.gitignore` — nếu `.env*` đang bị loại trừ thì **không** ép thêm vào git, chỉ ghi vào `README` cách tạo lại.

---

## Task 2: Nền móng — cấu hình, 3 bảng, 3 model

**Files:**
- Create: `config/inventory.php`
- Create: `database/migrations/2026_09_30_100000_create_inv_agents_table.php`
- Create: `database/migrations/2026_09_30_100100_create_inv_snapshots_table.php`
- Create: `database/migrations/2026_09_30_100200_create_inv_unmatched_table.php`
- Create: `app/Models/Inventory/InvAgent.php`, `InvSnapshot.php`, `InvUnmatched.php`
- Test: `tests/Feature/Inventory/InvTablesTest.php`

**Interfaces:**
- Produces:
  - `config('inventory.agent_user')`, `config('inventory.agent_secret')`, `config('inventory.inventory_interval_hours')`, `config('inventory.deploy_poll_hours')`, `config('inventory.snapshot_retention_count')`
  - `App\Models\Inventory\InvAgent` — `$deviceid`, `$agent_uuid`, `$hostname`, `$tag`, `$asset_id`, `$agent_version`, `$ip`, `$rustdesk_id`, `$last_contact_at`, `$last_inventory_at`, `$state`; quan hệ `snapshots()`
  - `App\Models\Inventory\InvSnapshot` — `$inv_agent_id`, `$asset_id`, `$payload`, `$content_hash`, `$protocol`, `$received_at`, `$processed_at`, `$error`; quan hệ `agent()`
  - `App\Models\Inventory\InvUnmatched`

- [ ] **Bước 1: Viết test đỏ**

Tạo `tests/Feature/Inventory/InvTablesTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvTablesTest extends TestCase
{
    public function test_can_register_an_agent_and_attach_a_snapshot(): void
    {
        $agent = InvAgent::create([
            'deviceid' => 'PC-045-2026-09-30-10-15-00',
            'agent_uuid' => Str::uuid()->toString(),
            'hostname' => 'PC-045',
            'tag' => 'PHONG-KE-TOAN',
            'agent_version' => '1.20',
        ]);

        $snapshot = InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => 'noi-dung-gia-lap',
            'content_hash' => str_repeat('a', 64),
            'protocol' => 'json',
            'received_at' => now(),
        ]);

        $this->assertSame($agent->id, $snapshot->agent->id);
        $this->assertCount(1, $agent->refresh()->snapshots);
    }

    public function test_deviceid_is_unique(): void
    {
        InvAgent::create(['deviceid' => 'TRUNG-NHAU']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        InvAgent::create(['deviceid' => 'TRUNG-NHAU']);
    }

    // Ban kiem ke that nang 1-3MB. Laravel binary() sinh BLOB (toi da 64KB)
    // nen migration PHAI doi thanh LONGBLOB - test nay chot dieu do lai.
    public function test_payload_column_can_hold_a_three_megabyte_snapshot(): void
    {
        $agent = InvAgent::create(['deviceid' => 'PC-TO']);

        $big = str_repeat('x', 3 * 1024 * 1024);

        InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => $big,
            'content_hash' => str_repeat('b', 64),
            'protocol' => 'json',
            'received_at' => now(),
        ]);

        $stored = DB::table('inv_snapshots')->where('inv_agent_id', $agent->id)->value('payload');
        $this->assertSame(strlen($big), strlen($stored));
    }
}
```

- [ ] **Bước 2: Chạy test để xác nhận ĐỎ**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=InvTablesTest
```

Kỳ vọng: **THẤT BẠI** với `Class "App\Models\Inventory\InvAgent" not found`.

- [ ] **Bước 3: Tạo `config/inventory.php`**

```php
<?php

return [
    // Chung thuc HTTP Basic voi tac tu. Agent KHONG gui duoc header tuy y -
    // Basic la co che duy nhat no ho tro (HTTP/Client.pm:271-300).
    'agent_user' => env('INVENTORY_AGENT_USER', 'qlts-agent'),
    'agent_secret' => env('INVENTORY_AGENT_SECRET'),

    // Nguong mac dinh - spec section 6.2
    'inventory_interval_hours' => (int) env('INVENTORY_INTERVAL_HOURS', 24),
    'deploy_poll_hours' => (int) env('INVENTORY_DEPLOY_POLL_HOURS', 4),
    'stale_inventory_days' => (int) env('INVENTORY_STALE_DAYS', 7),
    'stale_heartbeat_minutes' => (int) env('INVENTORY_STALE_HEARTBEAT_MINUTES', 15),
    'heartbeat_interval_minutes' => (int) env('INVENTORY_HEARTBEAT_INTERVAL_MINUTES', 5),
    'low_disk_percent' => (int) env('INVENTORY_LOW_DISK_PERCENT', 10),
    'battery_worn_percent' => (int) env('INVENTORY_BATTERY_WORN_PERCENT', 60),
    'heartbeat_retention_days' => (int) env('INVENTORY_HEARTBEAT_RETENTION_DAYS', 30),
    'snapshot_retention_count' => (int) env('INVENTORY_SNAPSHOT_RETENTION_COUNT', 10),
];
```

- [ ] **Bước 4: Tạo migration `inv_agents`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_agents', function (Blueprint $table) {
            $table->id();
            $table->string('deviceid')->unique();
            $table->string('agent_uuid', 36)->nullable()->unique();
            $table->string('hostname')->nullable()->index();
            $table->string('tag')->nullable()->index();
            // Khong dat khoa ngoai sang assets: tranh phu thuoc kieu cot cua
            // bang loi, va cho phep xoa tai san ma khong keo do ban kiem ke.
            $table->unsignedInteger('asset_id')->nullable()->index();
            $table->string('agent_version', 32)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('rustdesk_id', 32)->nullable()->index();
            $table->string('rustdesk_version', 32)->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('last_inventory_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->string('state', 16)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_agents');
    }
};
```

- [ ] **Bước 5: Tạo migration `inv_snapshots`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inv_agent_id')->constrained('inv_agents')->cascadeOnDelete();
            $table->unsignedInteger('asset_id')->nullable()->index();
            $table->binary('payload');
            $table->string('content_hash', 64)->index();
            $table->string('protocol', 8);
            $table->timestamp('received_at')->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // Laravel binary() sinh BLOB = toi da 64KB. Ban kiem ke that nang
        // 1-3MB nen phai nang len LONGBLOB bang cau lenh truc tiep.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE inv_snapshots MODIFY payload LONGBLOB NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_snapshots');
    }
};
```

- [ ] **Bước 6: Tạo migration `inv_unmatched`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_unmatched', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inv_agent_id')->constrained('inv_agents')->cascadeOnDelete();
            $table->string('hostname')->nullable();
            $table->string('serial')->nullable();
            $table->string('machine_uuid')->nullable();
            $table->string('reason', 64);
            $table->unsignedInteger('resolved_asset_id')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_unmatched');
    }
};
```

- [ ] **Bước 7: Tạo 3 model**

`app/Models/Inventory/InvAgent.php`:

```php
<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * So dang ky tac tu tren may tram.
 *
 * Ke thua Model thuan chu khong phai SnipeModel: bang nay khong phai tai san
 * nguoi dung sua tay, khong can pham vi theo cong ty (Companyable) cung khong
 * can tang kiem tra cua Watson\Validating.
 */
class InvAgent extends Model
{
    protected $table = 'inv_agents';

    protected $fillable = [
        'deviceid', 'agent_uuid', 'hostname', 'tag', 'asset_id',
        'agent_version', 'ip', 'rustdesk_id', 'rustdesk_version',
        'last_contact_at', 'last_inventory_at', 'last_heartbeat_at', 'state',
    ];

    protected $casts = [
        'asset_id' => 'integer',
        'last_contact_at' => 'datetime',
        'last_inventory_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
    ];

    public function snapshots(): HasMany
    {
        return $this->hasMany(InvSnapshot::class, 'inv_agent_id');
    }
}
```

`app/Models/Inventory/InvSnapshot.php`:

```php
<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvSnapshot extends Model
{
    protected $table = 'inv_snapshots';

    protected $fillable = [
        'inv_agent_id', 'asset_id', 'payload', 'content_hash',
        'protocol', 'received_at', 'processed_at', 'error',
    ];

    protected $casts = [
        'asset_id' => 'integer',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(InvAgent::class, 'inv_agent_id');
    }
}
```

`app/Models/Inventory/InvUnmatched.php`:

```php
<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvUnmatched extends Model
{
    protected $table = 'inv_unmatched';

    protected $fillable = [
        'inv_agent_id', 'hostname', 'serial', 'machine_uuid', 'reason',
        'resolved_asset_id', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'resolved_asset_id' => 'integer',
        'resolved_by' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(InvAgent::class, 'inv_agent_id');
    }
}
```

- [ ] **Bước 8: Chạy test để xác nhận XANH**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=InvTablesTest
```

Kỳ vọng: **3 test PASS**. Đặc biệt `test_payload_column_can_hold_a_three_megabyte_snapshot` phải xanh — nếu đỏ thì lệnh `ALTER TABLE ... LONGBLOB` chưa chạy.

- [ ] **Bước 9: Commit**

```bash
cd /d/DEV/Quanly-CNTT && git add config/inventory.php database/migrations app/Models/Inventory tests/Feature/Inventory && \
git commit -m "feat(inventory): them cau hinh va 3 bang nen mong cho duong ong kiem ke"
```

---

## Task 3: PayloadDecoder + SectionReader

**Files:**
- Create: `app/Services/Inventory/InvalidPayloadException.php`
- Create: `app/Services/Inventory/DecodedPayload.php`
- Create: `app/Services/Inventory/PayloadDecoder.php`
- Create: `app/Services/Inventory/SectionReader.php`
- Test: `tests/Unit/Inventory/PayloadDecoderTest.php`, `tests/Unit/Inventory/SectionReaderTest.php`

**Interfaces:**
- Produces:
  - `PayloadDecoder::decode(string $body, ?string $contentType): DecodedPayload` — ném `InvalidPayloadException` nếu không giải nén được
  - `DecodedPayload` — `readonly`, thuộc tính `string $protocol` (`'json'`|`'xml'`), `string $content`; phương thức `isJson(): bool`, `toArray(): array`
  - `SectionReader::section(array $content, string $name): array`
  - `SectionReader::rows(array $content, string $name): array` — luôn trả danh sách (mục 1 dòng được bọc thành mảng)
  - `SectionReader::value(array $row, string $name): mixed`

- [ ] **Bước 1: Viết test đỏ cho PayloadDecoder**

Tạo `tests/Unit/Inventory/PayloadDecoderTest.php`:

```php
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
        $this->decoder = new PayloadDecoder();
    }

    // Perl Compress::Zlib::compress() sinh luong zlib RFC1950 - PHP doc bang
    // gzcompress/gzuncompress (KHONG phai gzencode/gzdecode).
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

    // Mot so cau hinh agent gui zlib ma khong dat Content-Type dung. Doan
    // theo byte dau thay vi bo cuoc.
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

        $this->decoder->decode("\x01\x02\x03rac-ruoi", 'application/x-compress-zlib');
    }

    public function test_throws_on_empty_body(): void
    {
        $this->expectException(InvalidPayloadException::class);

        $this->decoder->decode('', null);
    }
}
```

- [ ] **Bước 2: Viết test đỏ cho SectionReader**

Tạo `tests/Unit/Inventory/SectionReaderTest.php`:

```php
<?php

namespace Tests\Unit\Inventory;

use App\Services\Inventory\SectionReader;
use PHPUnit\Framework\TestCase;

class SectionReaderTest extends TestCase
{
    // RB-4: giao thuc JSON gui khoa CHU THUONG (Protocol/Message.pm:44-76 ha
    // toan bo khoa truoc khi ma hoa), giao thuc XML gui CHU HOA. Moi lan doc
    // PHAI di qua day.
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

    // Agent gui mot ban ghi don le thanh hash, nhieu ban ghi thanh mang.
    // rows() luon phai tra ve danh sach.
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
        $this->assertNull(SectionReader::value(['ssn' => 'X'], 'khong-co'));
    }
}
```

- [ ] **Bước 3: Chạy 2 test để xác nhận ĐỎ**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter="PayloadDecoderTest|SectionReaderTest"
```

Kỳ vọng: **THẤT BẠI** với `Class "App\Services\Inventory\PayloadDecoder" not found`.

- [ ] **Bước 4: Tạo `InvalidPayloadException`**

```php
<?php

namespace App\Services\Inventory;

use RuntimeException;

class InvalidPayloadException extends RuntimeException
{
}
```

- [ ] **Bước 5: Tạo `DecodedPayload`**

```php
<?php

namespace App\Services\Inventory;

readonly class DecodedPayload
{
    public const PROTOCOL_JSON = 'json';

    public const PROTOCOL_XML = 'xml';

    public function __construct(
        public string $protocol,
        public string $content,
    ) {
    }

    public function isJson(): bool
    {
        return $this->protocol === self::PROTOCOL_JSON;
    }

    /**
     * Chi dung cho giao thuc JSON. Giao thuc XML do cac parser rieng xu ly.
     */
    public function toArray(): array
    {
        if (! $this->isJson()) {
            throw new InvalidPayloadException('toArray() chi dung cho giao thuc JSON');
        }

        $decoded = json_decode($this->content, true);

        if (! is_array($decoded)) {
            throw new InvalidPayloadException('Noi dung JSON khong hop le: '.json_last_error_msg());
        }

        return $decoded;
    }
}
```

- [ ] **Bước 6: Tạo `PayloadDecoder`**

```php
<?php

namespace App\Services\Inventory;

class PayloadDecoder
{
    public function decode(string $body, ?string $contentType): DecodedPayload
    {
        if ($body === '') {
            throw new InvalidPayloadException('Noi dung rong');
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
            // Content-Type khong noi gi hoac noi sai: thu doc truc tiep truoc,
            // roi moi doan nen. Mot so cau hinh agent dat sai header.
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
            'Khong giai ma duoc noi dung. Content-Type: '.($contentType ?: '(khong co')
            .', '.strlen($body).' byte, bat dau bang: '.bin2hex(substr($body, 0, 8))
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
```

- [ ] **Bước 7: Tạo `SectionReader`**

```php
<?php

namespace App\Services\Inventory;

/**
 * Diem thuc thi RB-4.
 *
 * Giao thuc JSON gui khoa CHU THUONG: Protocol/Message.pm:44-76 goi
 * converted() va _convert() ha toan bo khoa truoc khi ma hoa JSON. Giao thuc
 * XML cu gui CHU HOA. Tai lieu GLPI ghi chu hoa vi do la khuon XML.
 *
 * Moi lan doc muc hoac truong tu ban kiem ke PHAI di qua lop nay.
 */
class SectionReader
{
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
     * Luon tra ve danh sach ban ghi. Agent gui mot ban ghi don le thanh hash
     * (vd 'bios'), nhieu ban ghi thanh mang (vd 'memories').
     */
    public static function rows(array $content, string $name): array
    {
        $section = self::section($content, $name);

        if ($section === []) {
            return [];
        }

        return array_is_list($section) ? $section : [$section];
    }

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
```

- [ ] **Bước 8: Chạy test để xác nhận XANH**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter="PayloadDecoderTest|SectionReaderTest"
```

Kỳ vọng: **12 test PASS**.

- [ ] **Bước 9: Commit**

```bash
cd /d/DEV/Quanly-CNTT && git add app/Services/Inventory tests/Unit/Inventory && \
git commit -m "feat(inventory): giai nen zlib/gzip va doc muc khong phan biet hoa thuong"
```

---

## Task 4: ContactResponder

**Files:**
- Create: `app/Services/Inventory/ContactResponder.php`
- Test: `tests/Unit/Inventory/ContactResponderTest.php`

**Interfaces:**
- Consumes: `config('inventory.inventory_interval_hours')`, `config('inventory.deploy_poll_hours')`
- Produces: `ContactResponder::answer(): array` — cấu trúc CONTACT mà agent chấp nhận

- [ ] **Bước 1: Viết test đỏ**

Tạo `tests/Unit/Inventory/ContactResponderTest.php`:

```php
<?php

namespace Tests\Unit\Inventory;

use App\Services\Inventory\ContactResponder;
use Tests\TestCase;

class ContactResponderTest extends TestCase
{
    // Protocol/Contact.pm:31-40 - is_valid_message() doi PHAI co 'status' VA
    // expiration > 0. Thieu mot trong hai, agent coi day khong phai may chu
    // GLPI va quay ve giao thuc XML cu.
    public function test_answer_has_status_and_positive_expiration(): void
    {
        $answer = (new ContactResponder())->answer();

        $this->assertSame('ok', $answer['status']);
        $this->assertGreaterThan(0, $answer['expiration']);
    }

    public function test_answer_enables_inventory_and_deploy(): void
    {
        $answer = (new ContactResponder())->answer();

        $this->assertArrayHasKey('inventory', $answer['tasks']);
        $this->assertArrayHasKey('deploy', $answer['tasks']);
    }

    // RB-6 / spec 12.1: bat collect = mo cong chay lenh tuy y duoi quyen
    // SYSTEM tren ca 300 may. Tuyet doi khong.
    public function test_answer_never_enables_collect(): void
    {
        $answer = (new ContactResponder())->answer();

        $this->assertArrayNotHasKey('collect', $answer['tasks']);
    }

    public function test_frequencies_come_from_config(): void
    {
        config([
            'inventory.inventory_interval_hours' => 12,
            'inventory.deploy_poll_hours' => 2,
        ]);

        $answer = (new ContactResponder())->answer();

        $this->assertSame(12, $answer['expiration']);
        $this->assertSame(12, $answer['tasks']['inventory']['params'][0]['frequency']);
        $this->assertSame(2, $answer['tasks']['deploy']['params'][0]['frequency']);
    }
}
```

- [ ] **Bước 2: Chạy test để xác nhận ĐỎ**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=ContactResponderTest
```

Kỳ vọng: **THẤT BẠI** với `Class "App\Services\Inventory\ContactResponder" not found`.

- [ ] **Bước 3: Tạo `ContactResponder`**

```php
<?php

namespace App\Services\Inventory;

/**
 * Sinh cau tra loi CONTACT de agent chuyen sang giao thuc JSON.
 *
 * Protocol/Contact.pm:31-40: agent chi coi day la may chu GLPI khi cau tra
 * loi co 'status' VA 'expiration' > 0. Thieu thi no quay ve XML cu.
 */
class ContactResponder
{
    public function answer(): array
    {
        $inventoryHours = (int) config('inventory.inventory_interval_hours');
        $deployHours = (int) config('inventory.deploy_poll_hours');

        return [
            'status' => 'ok',
            'expiration' => $inventoryHours,
            'tasks' => [
                'inventory' => [
                    'params' => [
                        ['content' => '', 'frequency' => $inventoryHours, 'unit' => 'hour'],
                    ],
                ],
                'deploy' => [
                    'params' => [
                        ['content' => '', 'frequency' => $deployHours, 'unit' => 'hour'],
                    ],
                ],
                // RB-6: KHONG khai 'collect' o day. Xem spec 12.1.
            ],
        ];
    }
}
```

- [ ] **Bước 4: Chạy test để xác nhận XANH**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=ContactResponderTest
```

Kỳ vọng: **4 test PASS**.

- [ ] **Bước 5: Commit**

```bash
cd /d/DEV/Quanly-CNTT && git add app/Services/Inventory/ContactResponder.php tests/Unit/Inventory/ContactResponderTest.php && \
git commit -m "feat(inventory): tra loi CONTACT dung chuan de agent dung giao thuc JSON"
```

---

## Task 5: Route cho agent + chứng thực Basic

**Files:**
- Create: `app/Http/Middleware/VerifyAgentRequest.php`
- Create: `routes/agent.php`
- Modify: `app/Providers/RouteServiceProvider.php:22-28` (**file lõi duy nhất của GĐ1**)
- Test: `tests/Feature/Inventory/AgentAuthTest.php`

**Interfaces:**
- Consumes: `config('inventory.agent_user')`, `config('inventory.agent_secret')`
- Produces: route `POST /agent/inventory` tên `agent.inventory`, đã qua `VerifyAgentRequest`

- [ ] **Bước 1: Viết test đỏ**

Tạo `tests/Feature/Inventory/AgentAuthTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use Illuminate\Support\Str;
use Tests\TestCase;

class AgentAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'inventory.agent_user' => 'qlts-agent',
            'inventory.agent_secret' => 'ma-bi-mat',
        ]);
    }

    private function basic(): string
    {
        return 'Basic '.base64_encode('qlts-agent:ma-bi-mat');
    }

    // HTTP/Client.pm:271-300 - agent CHI gui lai kem chung thuc sau khi nhan
    // 401 CO header WWW-Authenticate. Thieu header nay agent bo cuoc im lang.
    public function test_missing_credentials_returns_401_with_basic_challenge(): void
    {
        $response = $this->postJson('/agent/inventory', []);

        $response->assertStatus(401);
        $this->assertStringContainsString('Basic realm=', $response->headers->get('WWW-Authenticate'));
    }

    public function test_wrong_password_returns_401(): void
    {
        $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('qlts-agent:sai-mat-khau'),
            'GLPI-Agent-ID' => Str::uuid()->toString(),
        ])->post('/agent/inventory', [])->assertStatus(401);
    }

    public function test_missing_agent_id_header_returns_400(): void
    {
        $this->withHeaders(['Authorization' => $this->basic()])
            ->post('/agent/inventory', [])
            ->assertStatus(400);
    }

    public function test_malformed_agent_id_returns_400(): void
    {
        $this->withHeaders([
            'Authorization' => $this->basic(),
            'GLPI-Agent-ID' => 'khong-phai-uuid',
        ])->post('/agent/inventory', [])->assertStatus(400);
    }

    // Route cua agent dang ky NGOAI nhom middleware 'web', nen VerifyCsrfToken
    // (app/Http/Kernel.php:76) khong chay. Test nay chot dieu do lai: khong co
    // token CSRF nao ma van khong bi 419.
    public function test_valid_request_is_not_blocked_by_csrf(): void
    {
        $response = $this->withHeaders([
            'Authorization' => $this->basic(),
            'GLPI-Agent-ID' => Str::uuid()->toString(),
            'Content-Type' => 'application/x-compress-zlib',
        ])->call('POST', '/agent/inventory', [], [], [], [], gzcompress('{"action":"contact","deviceid":"PC-045"}'));

        $this->assertNotSame(419, $response->getStatusCode());
        $this->assertNotSame(401, $response->getStatusCode());
        $this->assertNotSame(400, $response->getStatusCode());
    }
}
```

- [ ] **Bước 2: Chạy test để xác nhận ĐỎ**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=AgentAuthTest
```

Kỳ vọng: **THẤT BẠI** — route `/agent/inventory` chưa tồn tại, trả 404.

- [ ] **Bước 3: Tạo `VerifyAgentRequest`**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chung thuc tac tu bang HTTP Basic.
 *
 * Day la co che DUY NHAT GLPI Agent ho tro: no khong gui duoc header tuy y,
 * chi co 'user' / 'password' trong agent.cfg. Va theo
 * HTTP/Client.pm:271-300 no chi gui lai kem chung thuc SAU KHI nhan 401
 * kem header WWW-Authenticate - thieu header do thi agent bo cuoc im lang.
 */
class VerifyAgentRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('inventory.agent_user');
        $secret = (string) config('inventory.agent_secret');

        if ($secret === '') {
            return response('Chua cau hinh INVENTORY_AGENT_SECRET tren may chu', 503);
        }

        $sentUser = (string) $request->getUser();
        $sentPass = (string) $request->getPassword();

        if (! hash_equals($user, $sentUser) || ! hash_equals($secret, $sentPass)) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="QLTS Agent"',
            ]);
        }

        if (! Str::isUuid((string) $request->header('GLPI-Agent-ID'))) {
            return response('Thieu hoac sai header GLPI-Agent-ID', 400);
        }

        return $next($request);
    }
}
```

- [ ] **Bước 4: Tạo `routes/agent.php`**

```php
<?php

use App\Http\Controllers\Agent\InventoryIngestController;
use Illuminate\Support\Facades\Route;

/*
 * Route cho tac tu tren may tram.
 *
 * Dang ky NGOAI nhom middleware 'web' - nen khong co session, khong co
 * CSRF (VerifyCsrfToken chi nam trong nhom 'web', xem Kernel.php:76).
 * Middleware goi bang TEN CLASS chu khong phai alias, de khong phai sua
 * $middlewareAliases trong Kernel.php.
 */
Route::post('inventory', [InventoryIngestController::class, 'store'])
    ->name('agent.inventory');
```

- [ ] **Bước 5: Vá `RouteServiceProvider` — file lõi duy nhất của GĐ1**

Trong `app/Providers/RouteServiceProvider.php`, thêm `$this->mapAgentRoutes();` vào `boot()` (sau dòng 25) và thêm phương thức mới sau `mapWebRoutes()`:

```php
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            $this->mapApiRoutes();

            $this->mapWebRoutes();

            $this->mapAgentRoutes();

            require base_path('routes/scim.php');
        });
    }
```

```php
    /**
     * Route cho tac tu QLTS tren may tram (GLPI Agent).
     *
     * CO Y dat ngoai nhom middleware 'web': tac tu khong co session va khong
     * co token CSRF. Chung thuc bang HTTP Basic trong VerifyAgentRequest.
     */
    protected function mapAgentRoutes()
    {
        Route::group([
            'middleware' => [\App\Http\Middleware\VerifyAgentRequest::class],
            'prefix' => 'agent',
        ], function ($router) {
            require base_path('routes/agent.php');
        });
    }
```

- [ ] **Bước 6: Chạy test — sẽ vẫn ĐỎ vì controller chưa có**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=AgentAuthTest
```

Kỳ vọng: 3 test về 401/400 **PASS**; test `test_valid_request_is_not_blocked_by_csrf` **THẤT BẠI** vì `InventoryIngestController` chưa tồn tại. Đúng như mong đợi — Task 6 sẽ làm nó xanh.

- [ ] **Bước 7: Xác nhận route đã đăng ký và KHÔNG có middleware `web`**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe artisan route:list --path=agent
```

Kỳ vọng: thấy `POST agent/inventory ... agent.inventory`, cột middleware **chỉ có** `VerifyAgentRequest` — **không có** `web`.

- [ ] **Bước 8: Commit**

```bash
cd /d/DEV/Quanly-CNTT && git add app/Http/Middleware/VerifyAgentRequest.php routes/agent.php app/Providers/RouteServiceProvider.php tests/Feature/Inventory/AgentAuthTest.php && \
git commit -m "feat(inventory): route rieng cho tac tu, chung thuc HTTP Basic ngoai nhom web"
```

---

## Task 6: Controller — bắt tay CONTACT

**Files:**
- Create: `app/Http/Controllers/Agent/InventoryIngestController.php`
- Test: `tests/Feature/Inventory/ContactHandshakeTest.php`

**Interfaces:**
- Consumes: `PayloadDecoder`, `SectionReader`, `ContactResponder`, `InvAgent`
- Produces: `InventoryIngestController::store(Request $request)` — trả JSON CONTACT cho `action=contact`, ghi/cập nhật `inv_agents`

- [ ] **Bước 1: Viết test đỏ**

Tạo `tests/Feature/Inventory/ContactHandshakeTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\InvAgent;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContactHandshakeTest extends TestCase
{
    private string $agentUuid;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'inventory.agent_user' => 'qlts-agent',
            'inventory.agent_secret' => 'ma-bi-mat',
            'inventory.inventory_interval_hours' => 24,
            'inventory.deploy_poll_hours' => 4,
        ]);
        $this->agentUuid = Str::uuid()->toString();
    }

    private function sendContact(array $message)
    {
        return $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('qlts-agent:ma-bi-mat'),
            'GLPI-Agent-ID' => $this->agentUuid,
            'Content-Type' => 'application/x-compress-zlib',
        ])->call('POST', '/agent/inventory', [], [], [], [], gzcompress(json_encode($message)));
    }

    public function test_contact_returns_valid_contact_answer(): void
    {
        $response = $this->sendContact([
            'action' => 'contact',
            'deviceid' => 'PC-045-2026-09-30-10-15-00',
            'name' => 'GLPI-Agent',
            'version' => '1.20',
            'tag' => 'PHONG-KE-TOAN',
            'enabled-tasks' => ['inventory', 'deploy'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('expiration', 24)
            ->assertJsonPath('tasks.inventory.params.0.frequency', 24)
            ->assertJsonPath('tasks.deploy.params.0.frequency', 4);

        $this->assertArrayNotHasKey('collect', $response->json('tasks'));
    }

    public function test_contact_registers_the_agent(): void
    {
        $this->sendContact([
            'action' => 'contact',
            'deviceid' => 'PC-045-2026-09-30-10-15-00',
            'version' => '1.20',
            'tag' => 'PHONG-KE-TOAN',
        ])->assertOk();

        $agent = InvAgent::where('deviceid', 'PC-045-2026-09-30-10-15-00')->first();

        $this->assertNotNull($agent);
        $this->assertSame($this->agentUuid, $agent->agent_uuid);
        $this->assertSame('PHONG-KE-TOAN', $agent->tag);
        $this->assertSame('1.20', $agent->agent_version);
        $this->assertNotNull($agent->last_contact_at);
    }

    public function test_contact_twice_does_not_create_a_second_agent(): void
    {
        $message = ['action' => 'contact', 'deviceid' => 'PC-045', 'version' => '1.20'];

        $this->sendContact($message)->assertOk();
        $this->sendContact($message)->assertOk();

        $this->assertSame(1, InvAgent::where('deviceid', 'PC-045')->count());
    }

    // RB-4: khoa CHU HOA (giao thuc XML cu chuyen sang JSON) phai doc duoc.
    public function test_contact_accepts_uppercase_keys(): void
    {
        $this->sendContact([
            'ACTION' => 'contact',
            'DEVICEID' => 'PC-HOA',
            'VERSION' => '1.20',
        ])->assertOk()->assertJsonPath('status', 'ok');

        $this->assertNotNull(InvAgent::where('deviceid', 'PC-HOA')->first());
    }

    public function test_contact_without_deviceid_returns_400(): void
    {
        $this->sendContact(['action' => 'contact', 'version' => '1.20'])
            ->assertStatus(400);
    }

    public function test_unreadable_body_returns_400(): void
    {
        $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('qlts-agent:ma-bi-mat'),
            'GLPI-Agent-ID' => $this->agentUuid,
            'Content-Type' => 'application/x-compress-zlib',
        ])->call('POST', '/agent/inventory', [], [], [], [], "\x01\x02rac-ruoi")
            ->assertStatus(400);
    }
}
```

- [ ] **Bước 2: Chạy test để xác nhận ĐỎ**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=ContactHandshakeTest
```

Kỳ vọng: **THẤT BẠI** với `Target class [App\Http\Controllers\Agent\InventoryIngestController] does not exist`.

- [ ] **Bước 3: Tạo controller (chỉ nhánh contact — nhánh inventory làm ở Task 8)**

```php
<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InvAgent;
use App\Services\Inventory\ContactResponder;
use App\Services\Inventory\DecodedPayload;
use App\Services\Inventory\InvalidPayloadException;
use App\Services\Inventory\PayloadDecoder;
use App\Services\Inventory\SectionReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class InventoryIngestController extends Controller
{
    public function __construct(
        private readonly PayloadDecoder $decoder,
        private readonly ContactResponder $contactResponder,
    ) {
    }

    public function store(Request $request): JsonResponse|Response
    {
        try {
            $payload = $this->decoder->decode(
                $request->getContent(),
                $request->header('Content-Type')
            );
        } catch (InvalidPayloadException $e) {
            Log::warning('[qlts-agent] khong doc duoc noi dung: '.$e->getMessage());

            return response($e->getMessage(), 400);
        }

        // Giao thuc XML cu chi duoc dung o buoc bat tay: tra loi CONTACT JSON
        // de agent chuyen sang JSON cho moi lan sau (HTTP/Client/OCS.pm:90-110).
        if (! $payload->isJson()) {
            return response()->json($this->contactResponder->answer());
        }

        $message = $payload->toArray();
        $action = strtolower((string) SectionReader::value($message, 'action'));

        return match ($action) {
            'contact' => $this->handleContact($request, $message),
            default => response('Chua ho tro action: '.$action, 400),
        };
    }

    private function handleContact(Request $request, array $message): JsonResponse|Response
    {
        $deviceid = SectionReader::value($message, 'deviceid');

        if (! is_string($deviceid) || $deviceid === '') {
            return response('Thieu deviceid', 400);
        }

        InvAgent::updateOrCreate(
            ['deviceid' => $deviceid],
            [
                'agent_uuid' => $request->header('GLPI-Agent-ID'),
                'tag' => SectionReader::value($message, 'tag'),
                'agent_version' => SectionReader::value($message, 'version'),
                'ip' => $request->ip(),
                'last_contact_at' => now(),
                'state' => 'active',
            ]
        );

        return response()->json($this->contactResponder->answer());
    }
}
```

- [ ] **Bước 4: Chạy test để xác nhận XANH**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter="ContactHandshakeTest|AgentAuthTest"
```

Kỳ vọng: **12 test PASS** (7 của ContactHandshake + 5 của AgentAuth).

- [ ] **Bước 5: Commit**

```bash
cd /d/DEV/Quanly-CNTT && git add app/Http/Controllers/Agent tests/Feature/Inventory/ContactHandshakeTest.php && \
git commit -m "feat(inventory): bat tay CONTACT va dang ky tac tu"
```

---

## Task 7: AssetMatcher

**Files:**
- Create: `app/Services/Inventory/AssetMatcher.php`
- Test: `tests/Feature/Inventory/AssetMatcherTest.php`

**Interfaces:**
- Consumes: `App\Models\Asset`, `SectionReader`, `InvUnmatched`
- Produces: `AssetMatcher::match(array $content, InvAgent $agent): ?int` — trả `assets.id` hoặc `null`, và ghi `inv_unmatched` khi không khớp

- [ ] **Bước 1: Viết test đỏ**

Tạo `tests/Feature/Inventory/AssetMatcherTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvUnmatched;
use App\Services\Inventory\AssetMatcher;
use Tests\TestCase;

class AssetMatcherTest extends TestCase
{
    private InvAgent $agent;

    private AssetMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = InvAgent::create(['deviceid' => 'PC-TEST']);
        $this->matcher = new AssetMatcher();
    }

    public function test_matches_by_serial_from_bios_ssn(): void
    {
        $asset = Asset::factory()->create(['serial' => 'SN-ABC-123']);

        $id = $this->matcher->match(['bios' => ['ssn' => 'SN-ABC-123']], $this->agent);

        $this->assertSame($asset->id, $id);
    }

    public function test_serial_match_ignores_case_and_surrounding_spaces(): void
    {
        $asset = Asset::factory()->create(['serial' => 'SN-ABC-123']);

        $id = $this->matcher->match(['bios' => ['ssn' => '  sn-abc-123 ']], $this->agent);

        $this->assertSame($asset->id, $id);
    }

    public function test_falls_back_to_asset_tag_when_serial_does_not_match(): void
    {
        $asset = Asset::factory()->create(['serial' => 'KHAC', 'asset_tag' => 'TS-0045']);

        $id = $this->matcher->match([
            'bios' => ['ssn' => 'KHONG-CO-TRONG-SO'],
            'hardware' => ['name' => 'TS-0045'],
        ], $this->agent);

        $this->assertSame($asset->id, $id);
    }

    public function test_falls_back_to_asset_name(): void
    {
        $asset = Asset::factory()->create(['serial' => 'KHAC', 'name' => 'PC-KE-TOAN-02']);

        $id = $this->matcher->match(['hardware' => ['name' => 'PC-KE-TOAN-02']], $this->agent);

        $this->assertSame($asset->id, $id);
    }

    // RB-9: KHONG tu tao tai san moi. May la trong mang phai vao so cho gan tay.
    public function test_records_unmatched_and_creates_no_asset(): void
    {
        $before = Asset::count();

        $id = $this->matcher->match([
            'bios' => ['ssn' => 'MAY-LA-999'],
            'hardware' => ['name' => 'MAY-LA', 'uuid' => 'uuid-la'],
        ], $this->agent);

        $this->assertNull($id);
        $this->assertSame($before, Asset::count());

        $row = InvUnmatched::where('inv_agent_id', $this->agent->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('MAY-LA-999', $row->serial);
        $this->assertSame('MAY-LA', $row->hostname);
    }

    public function test_does_not_duplicate_unmatched_rows_for_the_same_agent(): void
    {
        $content = ['bios' => ['ssn' => 'MAY-LA-999'], 'hardware' => ['name' => 'MAY-LA']];

        $this->matcher->match($content, $this->agent);
        $this->matcher->match($content, $this->agent);

        $this->assertSame(1, InvUnmatched::where('inv_agent_id', $this->agent->id)->count());
    }

    // RB-8: tuyet doi khong khop theo RustDesk ID - ID nay trung/doi khi ghost may.
    public function test_never_matches_by_rustdesk_id(): void
    {
        Asset::factory()->create(['serial' => '16659046']);
        $this->agent->update(['rustdesk_id' => '16659046']);

        $id = $this->matcher->match([
            'remote_mgmt' => [['id' => '16659046', 'type' => 'rustdesk']],
        ], $this->agent);

        $this->assertNull($id);
    }

    public function test_prefers_serial_over_hostname_when_both_match_different_assets(): void
    {
        $bySerial = Asset::factory()->create(['serial' => 'SN-UU-TIEN']);
        Asset::factory()->create(['serial' => 'SN-KHAC', 'name' => 'TEN-MAY']);

        $id = $this->matcher->match([
            'bios' => ['ssn' => 'SN-UU-TIEN'],
            'hardware' => ['name' => 'TEN-MAY'],
        ], $this->agent);

        $this->assertSame($bySerial->id, $id);
    }
}
```

- [ ] **Bước 2: Chạy test để xác nhận ĐỎ**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=AssetMatcherTest
```

Kỳ vọng: **THẤT BẠI** với `Class "App\Services\Inventory\AssetMatcher" not found`.

- [ ] **Bước 3: Tạo `AssetMatcher`**

```php
<?php

namespace App\Services\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvUnmatched;

/**
 * Khop may tram voi tai san trong so.
 *
 * Thu tu: serial may (bios.ssn) -> ma tai san -> ten tai san. Dung o lan
 * khop dau tien.
 *
 * RB-8: TUYET DOI khong khop theo RustDesk ID - ID nay trung hoac doi khi
 * ghost/clone may, khop theo no se gan sai ho so tai san.
 *
 * RB-9: khong tao tai san moi. May la trong mang chi duoc ghi vao
 * inv_unmatched de can bo gan tay.
 */
class AssetMatcher
{
    public function match(array $content, InvAgent $agent): ?int
    {
        $serial = $this->clean(SectionReader::value(
            SectionReader::rows($content, 'bios')[0] ?? [], 'ssn'
        ));

        $hardware = SectionReader::rows($content, 'hardware')[0] ?? [];
        $hostname = $this->clean(SectionReader::value($hardware, 'name'));
        $machineUuid = $this->clean(SectionReader::value($hardware, 'uuid'));

        $id = $this->bySerial($serial)
            ?? $this->byAssetTag($hostname)
            ?? $this->byName($hostname);

        if ($id !== null) {
            return $id;
        }

        $this->recordUnmatched($agent, $hostname, $serial, $machineUuid);

        return null;
    }

    private function bySerial(?string $serial): ?int
    {
        if ($serial === null) {
            return null;
        }

        return Asset::whereRaw('LOWER(TRIM(serial)) = ?', [$serial])->value('id');
    }

    private function byAssetTag(?string $hostname): ?int
    {
        if ($hostname === null) {
            return null;
        }

        return Asset::whereRaw('LOWER(TRIM(asset_tag)) = ?', [$hostname])->value('id');
    }

    private function byName(?string $hostname): ?int
    {
        if ($hostname === null) {
            return null;
        }

        return Asset::whereRaw('LOWER(TRIM(name)) = ?', [$hostname])->value('id');
    }

    private function recordUnmatched(
        InvAgent $agent,
        ?string $hostname,
        ?string $serial,
        ?string $machineUuid
    ): void {
        InvUnmatched::updateOrCreate(
            ['inv_agent_id' => $agent->id],
            [
                'hostname' => $hostname,
                'serial' => $serial,
                'machine_uuid' => $machineUuid,
                'reason' => 'khong-tim-thay-tai-san-khop',
            ]
        );
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $clean = strtolower(trim($value));

        return $clean === '' ? null : $clean;
    }
}
```

> ⚠️ `Asset::factory()` của Snipe-IT có thể đòi thêm quan hệ bắt buộc (model, status label, company). Nếu test đỏ vì lỗi factory, đọc `database/factories/AssetFactory.php` và bổ sung đúng trạng thái cần thiết — **không** sửa factory lõi.

- [ ] **Bước 4: Chạy test để xác nhận XANH**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=AssetMatcherTest
```

Kỳ vọng: **8 test PASS**.

- [ ] **Bước 5: Commit**

```bash
cd /d/DEV/Quanly-CNTT && git add app/Services/Inventory/AssetMatcher.php tests/Feature/Inventory/AssetMatcherTest.php && \
git commit -m "feat(inventory): khop may tram voi tai san theo serial, ma tai san, ten"
```

---

## Task 8: Nhận bản kiểm kê + lưu RustDesk ID

**Files:**
- Modify: `app/Http/Controllers/Agent/InventoryIngestController.php` (thêm nhánh `inventory`)
- Create: `app/Jobs/Inventory/ProcessSnapshot.php`
- Test: `tests/Feature/Inventory/IngestInventoryTest.php`

**Interfaces:**
- Consumes: `AssetMatcher`, `InvSnapshot`, fixture thật từ GĐ0 Task 3 Bước 5
- Produces: `ProcessSnapshot` job — cập nhật `inv_agents.asset_id`, `rustdesk_id`, `last_inventory_at`; đánh dấu `inv_snapshots.processed_at`

- [ ] **Bước 1: Viết test đỏ**

Tạo `tests/Feature/Inventory/IngestInventoryTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use Illuminate\Support\Str;
use Tests\TestCase;

class IngestInventoryTest extends TestCase
{
    private string $agentUuid;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'inventory.agent_user' => 'qlts-agent',
            'inventory.agent_secret' => 'ma-bi-mat',
        ]);
        $this->agentUuid = Str::uuid()->toString();
    }

    private function send(array $message)
    {
        return $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('qlts-agent:ma-bi-mat'),
            'GLPI-Agent-ID' => $this->agentUuid,
            'Content-Type' => 'application/x-compress-zlib',
        ])->call('POST', '/agent/inventory', [], [], [], [], gzcompress(json_encode($message)));
    }

    private function inventoryMessage(array $overrides = []): array
    {
        return array_replace_recursive([
            'action' => 'inventory',
            'deviceid' => 'PC-045-2026-09-30-10-15-00',
            'content' => [
                'hardware' => ['name' => 'PC-045', 'uuid' => 'uuid-045'],
                'bios' => ['ssn' => 'SN-045', 'mmodel' => 'OptiPlex 7090'],
                'remote_mgmt' => [['id' => '16659046', 'type' => 'rustdesk']],
            ],
        ], $overrides);
    }

    public function test_inventory_is_stored_and_acknowledged(): void
    {
        $this->send($this->inventoryMessage())
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertSame(1, InvSnapshot::count());
    }

    public function test_snapshot_keeps_the_raw_compressed_payload(): void
    {
        $this->send($this->inventoryMessage())->assertOk();

        $snapshot = InvSnapshot::first();

        $this->assertSame('json', $snapshot->protocol);
        $this->assertSame(64, strlen($snapshot->content_hash));
        $this->assertNotEmpty($snapshot->payload);
    }

    public function test_inventory_links_the_agent_to_the_matching_asset(): void
    {
        $asset = Asset::factory()->create(['serial' => 'SN-045']);

        $this->send($this->inventoryMessage())->assertOk();

        $agent = InvAgent::where('deviceid', 'PC-045-2026-09-30-10-15-00')->first();

        $this->assertSame($asset->id, $agent->asset_id);
        $this->assertNotNull($agent->last_inventory_at);
    }

    // Day la ly do GD0 phai va RustDesk.pm: ID nay la dau vao cua GD4.
    public function test_inventory_stores_the_rustdesk_id(): void
    {
        Asset::factory()->create(['serial' => 'SN-045']);

        $this->send($this->inventoryMessage())->assertOk();

        $agent = InvAgent::where('deviceid', 'PC-045-2026-09-30-10-15-00')->first();

        $this->assertSame('16659046', $agent->rustdesk_id);
    }

    public function test_inventory_without_remote_mgmt_leaves_rustdesk_id_null(): void
    {
        Asset::factory()->create(['serial' => 'SN-045']);

        $message = $this->inventoryMessage();
        unset($message['content']['remote_mgmt']);

        $this->send($message)->assertOk();

        $this->assertNull(
            InvAgent::where('deviceid', 'PC-045-2026-09-30-10-15-00')->value('rustdesk_id')
        );
    }

    public function test_identical_inventory_sent_twice_stores_only_one_snapshot(): void
    {
        $message = $this->inventoryMessage();

        $this->send($message)->assertOk();
        $this->send($message)->assertOk();

        $this->assertSame(1, InvSnapshot::count());
    }

    public function test_snapshot_is_marked_processed(): void
    {
        Asset::factory()->create(['serial' => 'SN-045']);

        $this->send($this->inventoryMessage())->assertOk();

        // QUEUE_CONNECTION=sync trong phpunit.xml nen job chay ngay.
        $this->assertNotNull(InvSnapshot::first()->processed_at);
    }

    // Ban kiem ke THAT tu may that (GD0 Task 3 Buoc 5). Test nay bat moi
    // gia dinh sai ve khuon du lieu that.
    public function test_accepts_the_real_inventory_fixture(): void
    {
        $files = glob(base_path('tests/fixtures/inventory/*.json'));

        if ($files === false || $files === []) {
            $this->markTestSkipped('Chua co fixture that - chay GD0 Task 3 Buoc 5 truoc.');
        }

        $real = json_decode(file_get_contents($files[0]), true);
        $this->assertIsArray($real, 'Fixture khong phai JSON hop le');

        $this->send($real)->assertOk();

        $this->assertSame(1, InvSnapshot::count());
    }
}
```

- [ ] **Bước 2: Chạy test để xác nhận ĐỎ**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=IngestInventoryTest
```

Kỳ vọng: **THẤT BẠI** — controller trả 400 `Chua ho tro action: inventory`.

- [ ] **Bước 3: Tạo job `ProcessSnapshot`**

```php
<?php

namespace App\Jobs\Inventory;

use App\Models\Inventory\InvSnapshot;
use App\Services\Inventory\AssetMatcher;
use App\Services\Inventory\PayloadDecoder;
use App\Services\Inventory\SectionReader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phan tich ban kiem ke ngoai vong request.
 *
 * Voi 300+ may, ban kiem ke nang 1-3MB khong duoc phan tich trong request -
 * agent chi can biet may chu da NHAN duoc.
 *
 * GD1 chi lam phan toi thieu: khop tai san, luu RustDesk ID, dong dau da xu
 * ly. Cay linh kien day du la viec cua GD2.
 */
class ProcessSnapshot implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public int $snapshotId)
    {
    }

    public function handle(PayloadDecoder $decoder, AssetMatcher $matcher): void
    {
        $snapshot = InvSnapshot::with('agent')->find($this->snapshotId);

        if ($snapshot === null) {
            return;
        }

        try {
            $content = SectionReader::section(
                $decoder->decode($snapshot->payload, 'application/x-compress-zlib')->toArray(),
                'content'
            );

            $agent = $snapshot->agent;

            $assetId = $matcher->match($content, $agent);

            $agent->update([
                'asset_id' => $assetId,
                'hostname' => SectionReader::value(
                    SectionReader::rows($content, 'hardware')[0] ?? [], 'name'
                ),
                'rustdesk_id' => $this->rustdeskId($content),
                'last_inventory_at' => now(),
            ]);

            $snapshot->update([
                'asset_id' => $assetId,
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            Log::error('[qlts-agent] loi xu ly ban kiem ke '.$this->snapshotId.': '.$e->getMessage());

            $snapshot->update(['error' => $e->getMessage()]);

            throw $e;
        }
    }

    private function rustdeskId(array $content): ?string
    {
        foreach (SectionReader::rows($content, 'remote_mgmt') as $row) {
            $type = strtolower((string) SectionReader::value($row, 'type'));

            if ($type === 'rustdesk') {
                $id = SectionReader::value($row, 'id');

                return is_scalar($id) && (string) $id !== '' ? (string) $id : null;
            }
        }

        return null;
    }
}
```

- [ ] **Bước 4: Thêm nhánh `inventory` vào controller**

Constructor **không đổi** — `AssetMatcher` do job tự nhận qua container, controller không cần nó.

Thêm 2 dòng `use` vào đầu `InventoryIngestController` (các `use` khác đã có từ Task 6):

```php
use App\Jobs\Inventory\ProcessSnapshot;
use App\Models\Inventory\InvSnapshot;
```

Sửa `match` trong `store()`:

```php
        return match ($action) {
            'contact' => $this->handleContact($request, $message),
            'inventory' => $this->handleInventory($request, $message, $payload),
            default => response('Chua ho tro action: '.$action, 400),
        };
```

Và thêm phương thức:

```php
    private function handleInventory(
        Request $request,
        array $message,
        DecodedPayload $payload
    ): JsonResponse|Response {
        $deviceid = SectionReader::value($message, 'deviceid');

        if (! is_string($deviceid) || $deviceid === '') {
            return response('Thieu deviceid', 400);
        }

        $agent = InvAgent::updateOrCreate(
            ['deviceid' => $deviceid],
            [
                'agent_uuid' => $request->header('GLPI-Agent-ID'),
                'ip' => $request->ip(),
                'last_contact_at' => now(),
                'state' => 'active',
            ]
        );

        $hash = hash('sha256', $payload->content);

        // Agent gui lai y nguyen ban cu khi khong co gi doi. Bo qua de khong
        // phinh bang: 300 may x 1 ban/ngay x 2MB = 600MB moi ngay.
        $existing = InvSnapshot::where('inv_agent_id', $agent->id)
            ->where('content_hash', $hash)
            ->first();

        if ($existing !== null) {
            $agent->update(['last_inventory_at' => now()]);

            return response()->json(['status' => 'ok']);
        }

        $snapshot = InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => gzcompress($payload->content),
            'content_hash' => $hash,
            'protocol' => $payload->protocol,
            'received_at' => now(),
        ]);

        ProcessSnapshot::dispatch($snapshot->id);

        return response()->json(['status' => 'ok']);
    }
```

- [ ] **Bước 5: Chạy test để xác nhận XANH**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=IngestInventoryTest
```

Kỳ vọng: **8 test PASS** (test fixture thật có thể `SKIP` nếu chưa chạy GĐ0).

- [ ] **Bước 6: Chạy toàn bộ test của phân hệ để chắc không làm vỡ gì**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=Inventory
```

Kỳ vọng: **tất cả PASS**.

- [ ] **Bước 7: Commit**

```bash
cd /d/DEV/Quanly-CNTT && git add app/Jobs/Inventory app/Http/Controllers/Agent tests/Feature/Inventory/IngestInventoryTest.php && \
git commit -m "feat(inventory): nhan ban kiem ke, khop tai san, luu RustDesk ID"
```

---

## Task 9: Hai lệnh artisan — `inv:import` và `inv:doctor`

**Files:**
- Create: `app/Console/Commands/Inventory/ImportInventoryFile.php`
- Create: `app/Console/Commands/Inventory/InventoryDoctor.php`
- Test: `tests/Feature/Inventory/InvImportCommandTest.php`

**Interfaces:**
- Produces: `artisan inv:import <file>`, `artisan inv:doctor`

- [ ] **Bước 1: Viết test đỏ**

Tạo `tests/Feature/Inventory/InvImportCommandTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use Tests\TestCase;

class InvImportCommandTest extends TestCase
{
    public function test_imports_an_inventory_json_file_without_an_agent(): void
    {
        Asset::factory()->create(['serial' => 'SN-IMPORT']);

        $file = tempnam(sys_get_temp_dir(), 'inv').'.json';
        file_put_contents($file, json_encode([
            'action' => 'inventory',
            'deviceid' => 'PC-IMPORT',
            'content' => [
                'hardware' => ['name' => 'PC-IMPORT'],
                'bios' => ['ssn' => 'SN-IMPORT'],
                'remote_mgmt' => [['id' => '99887766', 'type' => 'rustdesk']],
            ],
        ]));

        $this->artisan('inv:import', ['file' => $file])
            ->expectsOutputToContain('PC-IMPORT')
            ->assertExitCode(0);

        unlink($file);

        $agent = InvAgent::where('deviceid', 'PC-IMPORT')->first();

        $this->assertNotNull($agent);
        $this->assertSame('99887766', $agent->rustdesk_id);
        $this->assertSame(1, InvSnapshot::count());
    }

    public function test_fails_cleanly_when_the_file_does_not_exist(): void
    {
        $this->artisan('inv:import', ['file' => '/khong/co/file.json'])
            ->assertExitCode(1);
    }

    public function test_doctor_reports_on_the_nine_core_files(): void
    {
        $this->artisan('inv:doctor')
            ->expectsOutputToContain('RouteServiceProvider')
            ->assertExitCode(0);
    }
}
```

- [ ] **Bước 2: Chạy test để xác nhận ĐỎ**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=InvImportCommandTest
```

Kỳ vọng: **THẤT BẠI** với `The command "inv:import" does not exist.`

- [ ] **Bước 3: Tạo `ImportInventoryFile`**

```php
<?php

namespace App\Console\Commands\Inventory;

use App\Jobs\Inventory\ProcessSnapshot;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use App\Services\Inventory\SectionReader;
use Illuminate\Console\Command;

/**
 * Nap mot ban kiem ke tu file, khong can agent that.
 *
 * Dung de phat trien va de xu ly su co: agent co the xuat file bang
 * `glpi-agent --local=<thu-muc> --json` roi nap tay o day.
 */
class ImportInventoryFile extends Command
{
    protected $signature = 'inv:import {file : Duong dan file kiem ke .json}';

    protected $description = 'Nap mot ban kiem ke JSON vao he thong (khong can agent)';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! is_file($file)) {
            $this->error("Khong tim thay file: {$file}");

            return self::FAILURE;
        }

        $message = json_decode((string) file_get_contents($file), true);

        if (! is_array($message)) {
            $this->error('File khong phai JSON hop le: '.json_last_error_msg());

            return self::FAILURE;
        }

        $deviceid = SectionReader::value($message, 'deviceid');

        if (! is_string($deviceid) || $deviceid === '') {
            $this->error('File thieu truong deviceid');

            return self::FAILURE;
        }

        $agent = InvAgent::updateOrCreate(
            ['deviceid' => $deviceid],
            ['last_contact_at' => now(), 'state' => 'active']
        );

        $content = json_encode($message);

        $snapshot = InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => gzcompress($content),
            'content_hash' => hash('sha256', $content),
            'protocol' => 'json',
            'received_at' => now(),
        ]);

        ProcessSnapshot::dispatchSync($snapshot->id);

        $agent->refresh();

        $this->info("Da nap ban kiem ke cua {$deviceid}");
        $this->line('  Ma tai san khop : '.($agent->asset_id ?? 'CHUA KHOP'));
        $this->line('  RustDesk ID     : '.($agent->rustdesk_id ?? 'khong co'));

        return self::SUCCESS;
    }
}
```

- [ ] **Bước 4: Tạo `InventoryDoctor`**

```php
<?php

namespace App\Console\Commands\Inventory;

use Illuminate\Console\Command;

/**
 * Kiem 9 file loi con du ban va hay da bi nang cap Snipe-IT ghi de.
 *
 * Chay sau moi lan cap nhat Snipe-IT. Xem spec section 13.
 */
class InventoryDoctor extends Command
{
    protected $signature = 'inv:doctor';

    protected $description = 'Kiem cac ban va tren file loi Snipe-IT con nguyen hay khong';

    /**
     * duong-dan => [dau-hieu-can-tim, giai-doan, viec-phai-lam-neu-mat]
     */
    private const PATCHES = [
        'app/Providers/RouteServiceProvider.php' => [
            'mapAgentRoutes',
            'GD1',
            'Them $this->mapAgentRoutes() vao boot() va phuong thuc mapAgentRoutes()',
        ],
    ];

    public function handle(): int
    {
        $missing = 0;

        foreach (self::PATCHES as $path => [$needle, $phase, $todo]) {
            $full = base_path($path);

            if (! is_file($full)) {
                $this->error("[{$phase}] THIEU FILE  {$path}");
                $missing++;

                continue;
            }

            if (str_contains((string) file_get_contents($full), $needle)) {
                $this->info("[{$phase}] OK          {$path}");

                continue;
            }

            $this->error("[{$phase}] MAT BAN VA  {$path}");
            $this->line("             Can lam: {$todo}");
            $missing++;
        }

        if ($missing > 0) {
            $this->newLine();
            $this->warn("Co {$missing} ban va can chen lai. Xem docs/superpowers/specs/2026-09-30-tich-hop-tac-tu-qlts-design.md muc 13.");
        }

        return self::SUCCESS;
    }
}
```

> Mỗi giai đoạn sau khi vá thêm file lõi **phải bổ sung một dòng vào `self::PATCHES`**. Đây là cách duy nhất `inv:doctor` còn hữu dụng về sau.

- [ ] **Bước 5: Chạy test để xác nhận XANH**

```bash
cd /d/DEV/Quanly-CNTT && ./tools/php/php.exe vendor/bin/phpunit --filter=InvImportCommandTest
```

Kỳ vọng: **3 test PASS**.

> Snipe-IT nạp lệnh artisan bằng `$this->load(__DIR__.'/Commands')` trong `app/Console/Kernel.php` — thư mục con `Commands/Inventory/` được nhận tự động, **không cần vá `Kernel.php`**. Nếu lệnh không xuất hiện, kiểm `artisan list | grep inv:` rồi mới xét việc khai báo tay.

- [ ] **Bước 6: Commit**

```bash
cd /d/DEV/Quanly-CNTT && git add app/Console/Commands/Inventory tests/Feature/Inventory/InvImportCommandTest.php && \
git commit -m "feat(inventory): lenh inv:import va inv:doctor"
```

---

## Cổng kiểm soát GĐ1 — phải dán được kết quả thật

Theo `superpowers:verification-before-completion`, **không được** tuyên bố GĐ1 xong nếu chưa dán ra kết quả:

| # | Lệnh | Kỳ vọng |
|---|---|---|
| 1 | `./tools/php/php.exe vendor/bin/phpunit --filter=Inventory` | Tất cả PASS (≈38 test) |
| 2 | `./tools/php/php.exe artisan route:list --path=agent` | `POST agent/inventory`, middleware **chỉ** `VerifyAgentRequest`, **không có** `web` |
| 3 | `./tools/php/php.exe artisan inv:import tests/fixtures/inventory/<file>.json` | In ra deviceid + mã tài sản khớp + RustDesk ID |
| 4 | `./tools/php/php.exe artisan inv:doctor` | `[GD1] OK  app/Providers/RouteServiceProvider.php` |
| 5 | Agent thật: sửa `qlts.cfg` trỏ về Snipe-IT rồi `glpi-agent.bat --force --debug` | Log agent có `contact` rồi `inventory`; **không** có `sending message` dạng XML ở lần thứ hai (đã chuyển JSON) |
| 6 | `SELECT deviceid, asset_id, rustdesk_id, last_inventory_at FROM inv_agents;` | Có dòng của máy thật, `rustdesk_id` **không NULL** |
| 7 | `git diff --stat HEAD~9 -- app/ config/ routes/ resources/` | **Đúng 1 file lõi bị sửa**: `app/Providers/RouteServiceProvider.php` |

Riêng cổng số 7 là cách kiểm RB-2 bằng máy: nếu có file lõi thứ hai xuất hiện, **dừng và xin duyệt lại**.
