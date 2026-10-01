@extends('layouts/default')

@section('title')
{{ $report['title'] }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $report['title'] }} ({{ count($report['rows']) }})</h3>
                <div class="box-tools">
                    <a class="btn btn-sm btn-default" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">Excel (CSV)</a>
                    <a class="btn btn-sm btn-default" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">PDF</a>
                </div>
            </div>
            <div class="box-body">
                @if ($key === 'changes')
                    <form method="GET" class="form-inline" style="margin-bottom: 15px">
                        <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                        <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                        <input type="text" name="section" class="form-control" placeholder="memories, storages…" value="{{ request('section') }}">
                        <select name="location_id" class="form-control">
                            <option value="">— {{ trans('admin/inventory/reports.all_departments') }} —</option>
                            @foreach ($locations as $loc)<option value="{{ $loc->id }}" @selected(request('location_id') == $loc->id)>{{ $loc->name }}</option>@endforeach
                        </select>
                        <button class="btn btn-primary">{{ trans('general.search') }}</button>
                    </form>
                @endif
                <div class="table-responsive">
                    <table class="table table-striped table-condensed">
                        <thead><tr>@foreach ($report['headers'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($report['rows'] as $row)
                                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
