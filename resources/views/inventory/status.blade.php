@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/status.title') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                <p>{{ trans('admin/inventory/status.summary', ['online' => $online, 'total' => $agents->count()]) }}</p>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th></th>
                            <th>{{ trans('admin/inventory/status.machine') }}</th>
                            <th>{{ trans('admin/inventory/status.user') }}</th>
                            <th>IP</th>
                            <th>CPU %</th>
                            <th>RAM %</th>
                            <th>{{ trans('admin/inventory/status.disk_free') }}</th>
                            <th>{{ trans('admin/inventory/status.last_heartbeat') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agents as $a)
                            @php($h = $a->latestHeartbeat)
                            @php($free = $h?->lowestFreePercent())
                            <tr>
                                <td><span class="label {{ $a->isOnline() ? 'label-success' : 'label-danger' }}">{{ $a->isOnline() ? trans('admin/inventory/status.online') : trans('admin/inventory/status.offline') }}</span></td>
                                <td>
                                    @if ($a->asset_id)<a href="{{ route('hardware.show', $a->asset_id) }}">{{ $a->hostname ?: $a->deviceid }}</a>@else{{ $a->hostname ?: $a->deviceid }}@endif
                                </td>
                                <td>{{ $h?->logged_user }}</td>
                                <td>{{ $h?->ip ?? $a->ip }}</td>
                                <td>{{ $h?->cpu_percent }}</td>
                                <td>{{ $h?->ram_percent }}</td>
                                <td class="{{ $free !== null && $free < $lowDisk ? 'text-danger' : '' }}">{{ $free !== null ? round($free).'%' : '' }}</td>
                                <td>{{ $a->last_heartbeat_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
