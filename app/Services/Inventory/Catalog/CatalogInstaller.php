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
