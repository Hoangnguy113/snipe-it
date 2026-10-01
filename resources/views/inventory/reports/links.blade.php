{{-- 4 liên kết báo cáo kiểm kê (GD7). Một dòng @include trong reports/index.blade.php. --}}
@foreach (\App\Services\Inventory\Reports\InventoryReports::KEYS as $key)
    <div class="col-md-3 col-sm-6">
        <a href="{{ route('inventory.reports', $key) }}" class="btn btn-theme btn-block" style="margin-bottom: 10px; white-space: normal; text-align: left; padding-left: 15px;">
            <i class="fas fa-microchip fa-fw" aria-hidden="true"></i> {{ trans('admin/inventory/reports.'.$key) }}
        </a>
    </div>
@endforeach
