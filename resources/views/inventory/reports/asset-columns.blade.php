{{-- Cột kiểm kê cho "Điều chỉnh báo cáo tài sản" (GD7). Một dòng @include trong reports/custom/asset.blade.php. --}}
<h2>{{ trans('admin/inventory/tree.tab') }}: </h2>
@foreach (\App\Services\Inventory\Reports\ColumnExtension::COLUMNS as $key => $label)
    <label class="form-control">
        <input type="checkbox" name="{{ $key }}" value="1" @checked($template->checkmarkValue($key)) />
        {{ $label }}
    </label>
@endforeach
