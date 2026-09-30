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

// Chạy trên DB test MySQL; chậm vì tạo CustomField chạy ALTER TABLE assets + migrate lại.
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
