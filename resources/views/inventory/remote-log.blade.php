@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/remote.log_title') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                <form method="GET" class="form-inline" style="margin-bottom: 15px">
                    <input type="number" name="user_id" class="form-control" placeholder="{{ trans('admin/inventory/remote.user') }} ID" value="{{ request('user_id') }}">
                    <input type="number" name="asset_id" class="form-control" placeholder="{{ trans('general.asset') }} ID" value="{{ request('asset_id') }}">
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                    <button class="btn btn-primary">{{ trans('general.search') }}</button>
                </form>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/inventory/remote.when') }}</th>
                            <th>{{ trans('admin/inventory/remote.user') }}</th>
                            <th>{{ trans('general.asset') }}</th>
                            <th>RustDesk ID</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $s)
                            <tr>
                                <td>{{ $s->started_at }}</td>
                                <td>{{ $s->user?->username }}</td>
                                <td>@if ($s->asset)<a href="{{ route('hardware.show', $s->asset_id) }}">{{ $s->asset->asset_tag }}</a>@endif</td>
                                <td>{{ $s->rustdesk_id }}</td>
                                <td>{{ $s->operator_ip }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $sessions->links() }}
            </div>
        </div>
    </div>
</div>
@stop
