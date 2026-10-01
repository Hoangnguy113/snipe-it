@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/approvals.title') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">{{ trans('admin/inventory/approvals.changes_title') }} ({{ $changes->count() }})</h3></div>
            <div class="box-body">
                @if ($changes->isEmpty())
                    <p class="text-muted">{{ trans('admin/inventory/approvals.none') }}</p>
                @else
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ trans('general.asset') }}</th>
                                <th>{{ trans('admin/inventory/approvals.section') }}</th>
                                <th>{{ trans('admin/inventory/approvals.old') }}</th>
                                <th>{{ trans('admin/inventory/approvals.new') }}</th>
                                <th>{{ trans('admin/inventory/approvals.severity') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($changes as $c)
                                <tr class="{{ $c->severity === 'critical' ? 'danger' : '' }}">
                                    <td>@if ($c->asset)<a href="{{ route('hardware.show', $c->asset_id) }}">{{ $c->asset->asset_tag }}</a>@endif</td>
                                    <td>{{ $c->section }} / {{ trans('admin/inventory/approvals.type_'.$c->change_type) }}@if ($c->field && $c->field !== '*') ({{ $c->field }})@endif</td>
                                    <td><small>{{ $c->old_value }}</small></td>
                                    <td><small>{{ $c->new_value }}</small></td>
                                    <td>{{ $c->severity }}</td>
                                    <td class="text-right" style="white-space: nowrap">
                                        <form method="POST" action="{{ route('inventory.changes.approve', $c) }}" style="display:inline">@csrf
                                            <button class="btn btn-sm btn-success">{{ trans('admin/inventory/approvals.approve') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('inventory.changes.reject', $c) }}" style="display:inline">@csrf
                                            <button class="btn btn-sm btn-default">{{ trans('admin/inventory/approvals.reject') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">{{ trans('admin/inventory/approvals.tags_title') }} ({{ $tags->count() }})</h3></div>
            <div class="box-body">
                @if ($tags->isEmpty())
                    <p class="text-muted">{{ trans('admin/inventory/approvals.none') }}</p>
                @else
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ trans('admin/inventory/approvals.tag') }}</th>
                                <th>{{ trans('admin/inventory/approvals.agent_count') }}</th>
                                <th>{{ trans('admin/inventory/approvals.assign') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tags as $t)
                                <tr>
                                    <td>{{ $t->tag }}</td>
                                    <td>{{ $t->agent_count }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('inventory.tags.assign', $t) }}" class="form-inline">@csrf
                                            <select name="location_id" class="form-control">
                                                <option value="">{{ trans('admin/inventory/approvals.pick_location') }}</option>
                                                @foreach (\App\Models\Location::orderBy('name')->get(['id', 'name']) as $loc)
                                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="new_location_name" class="form-control" placeholder="{{ trans('admin/inventory/approvals.new_location') }}">
                                            <button class="btn btn-sm btn-success">{{ trans('admin/inventory/approvals.approve') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('inventory.tags.ignore', $t) }}" style="display:inline">@csrf
                                            <button class="btn btn-sm btn-default">{{ trans('admin/inventory/approvals.ignore') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
