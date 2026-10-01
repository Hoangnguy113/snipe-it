@php
    $reader = app(\App\Services\Inventory\Tree\TreeReader::class);
    $tree = $reader->forAsset($asset->id);
    $lastSeen = $reader->lastSeenAt($asset->id);
    $labels = trans('admin/inventory/tree.columns');
@endphp

@if ($lastSeen === null)
    <p class="text-muted" style="padding: 15px;">{{ trans('admin/inventory/tree.empty') }}</p>
@else
    <p class="text-muted">{{ trans('admin/inventory/tree.last_seen') }}: {{ $lastSeen }}</p>

    @foreach (\App\Services\Inventory\Tree\TreeReader::layout() as $section => $columns)
        @continue($tree[$section]->isEmpty())
        <h4>{{ trans('admin/inventory/tree.sections.'.$section) }} <small>({{ $tree[$section]->count() }})</small></h4>
        <div class="table-responsive">
            <table class="table table-striped table-condensed">
                <thead>
                    <tr>
                        @foreach ($columns as $col)
                            <th>{{ $labels[$col] ?? $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tree[$section] as $row)
                        <tr>
                            @foreach ($columns as $col)
                                @php($v = $row->{$col})
                                <td>
                                    @if (is_null($v))
                                        &nbsp;
                                    @elseif (in_array($col, ['is_enabled', 'is_uptodate'], true))
                                        {{ $v ? trans('admin/inventory/tree.yes') : trans('admin/inventory/tree.no') }}
                                    @else
                                        {{ $v }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endif
