@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/deploy.jobs_title') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        @unless ($enabled)
            <div class="alert alert-warning">{{ trans('admin/inventory/deploy.disabled') }}</div>
        @endunless
        <div class="box box-default">
            <div class="box-body">
                <form method="POST" action="{{ route('inventory.deploy.store') }}" class="form-inline" style="margin-bottom: 15px">
                    @csrf
                    <select name="package_id" class="form-control" required>
                        @foreach ($packages as $p)<option value="{{ $p->id }}">{{ $p->name }} {{ $p->version }}</option>@endforeach
                    </select>
                    <select name="scope_type" class="form-control">
                        <option value="asset">{{ trans('admin/inventory/deploy.scope_asset') }}</option>
                        <option value="location">{{ trans('admin/inventory/deploy.scope_location') }}</option>
                    </select>
                    <input type="text" name="scope_ids" class="form-control" placeholder="{{ trans('admin/inventory/deploy.scope_ids') }}" required>
                    <button class="btn btn-primary">{{ trans('admin/inventory/deploy.send') }}</button>
                </form>
                @foreach ($jobs as $job)
                    <h4>{{ $job->package->name }} <small>{{ $job->created_at }} — {{ $job->targets->where('state', 'ok')->count() }}/{{ $job->targets->count() }} {{ trans('admin/inventory/deploy.done') }}</small></h4>
                    <table class="table table-condensed table-striped">
                        <tbody>
                            @foreach ($job->targets as $t)
                                <tr>
                                    <td>{{ $t->agent?->hostname ?: $t->agent?->deviceid }}</td>
                                    <td><span class="label {{ ['ok' => 'label-success', 'failed' => 'label-danger', 'running' => 'label-info'][$t->state] ?? 'label-default' }}">{{ trans('admin/inventory/deploy.state_'.$t->state) }}</span></td>
                                    <td><small>{{ \Illuminate\Support\Str::limit($t->log, 160) }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        </div>
    </div>
</div>
@stop
