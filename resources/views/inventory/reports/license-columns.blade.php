{{-- Hai cột đối chiếu bản quyền (GD7). Dùng 2 lần trong reports/licenses.blade.php: ở <thead> (không có $license) và ở mỗi dòng (có $license). --}}
@if (isset($license))
    @php($installed = \App\Services\Inventory\Reports\ColumnExtension::installedPerLicense()[$license->id] ?? 0)
    <td class="text-right">{{ $installed }}</td>
    <td class="text-right">{{ $installed - (int) $license->seats }}</td>
@else
    <th scope="col" class="col-sm-1 text-right">Số máy thực cài</th>
    <th scope="col" class="col-sm-1 text-right">Chênh lệch so với ghế đã mua</th>
@endif
