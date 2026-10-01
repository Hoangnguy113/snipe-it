{{-- Cột "Lần agent báo cuối" cho Nhật ký kiểm kê (GD7). Một dòng @include trong <thead> của reports/audit.blade.php. --}}
<script>
    window.invLastAgent = @json(\App\Services\Inventory\Reports\ColumnExtension::lastAgentByAsset());
    function invLastAgentFormatter(value) {
        return (value && window.invLastAgent[value.id]) ? window.invLastAgent[value.id] : '';
    }
</script>
<th scope="col" class="col-sm-2" data-field="item" data-formatter="invLastAgentFormatter">Lần agent báo cuối</th>
