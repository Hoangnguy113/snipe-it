@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/approvals.removed_title') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ trans('general.asset') }}</th>
                            <th>{{ trans('admin/inventory/approvals.section') }}</th>
                            <th>{{ trans('admin/inventory/approvals.part_name') }}</th>
                            <th>Serial</th>
                            <th>{{ trans('admin/inventory/approvals.condition') }}</th>
                            <th>{{ trans('admin/inventory/approvals.disposal') }}</th>
                            <th>{{ trans('admin/inventory/approvals.detected') }}</th>
                            <th>{{ trans('general.maintenance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parts as $p)
                            <tr>
                                <td>@if ($p->asset)<a href="{{ route('hardware.show', $p->asset_id) }}">{{ $p->asset->asset_tag }}</a>@endif</td>
                                <td>{{ $p->part_type }}</td>
                                <td>{{ $p->name }}</td>
                                <td>{{ $p->serial }}</td>
                                <td>{{ $p->condition }}</td>
                                <td>{{ $p->disposal_state }}</td>
                                <td>{{ $p->detected_at }}</td>
                                <td>@if ($p->maintenance_id)<a href="{{ route('maintenances.show', $p->maintenance_id) }}">#{{ $p->maintenance_id }}</a>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
