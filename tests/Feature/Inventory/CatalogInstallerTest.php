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

// Tạo CustomField chạy ALTER TABLE assets nên mỗi test DDL chậm (Laravel migrate lại giữa các test).
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
        (new CatalogInstaller($this->definition()))->install(User::factory()->superuser()->create());

        $this->assertDatabaseHas('categories', ['name' => 'Hộp mực thử', 'category_type' => 'consumable']);
        $this->assertDatabaseMissing('custom_fieldsets', ['name' => CatalogInstaller::FIELDSET_PREFIX.'Hộp mực thử']);
        $this->assertDatabaseMissing('models', ['name' => CatalogInstaller::MODEL_PREFIX.'Hộp mực thử']);
    }

    public function test_installing_twice_creates_nothing_the_second_time(): void
    {
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

    public function test_command_fails_without_a_superuser(): void
    {
        // Ensure no superusers exist
        User::where('permissions->superuser', '1')->delete();

        $this->artisan('inv:catalog-install')
            ->expectsOutputToContain('superuser')
            ->assertExitCode(1);
    }

    public function test_command_installs_and_reports_counts(): void
    {
        config(['inventory_catalog' => $this->definition()]);
        User::factory()->superuser()->create();

        $this->artisan('inv:catalog-install')
            ->expectsOutputToContain('categories: 2')
            ->expectsOutputToContain('fields: 3')
            ->assertExitCode(0);

        $this->assertDatabaseHas('categories', ['name' => 'Máy in thử']);
    }
}
