@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/inventory/catalog.title') }}
@parent
@stop

{{-- Page content --}}
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                <p class="text-muted">{{ trans('admin/inventory/catalog.intro') }}</p>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/inventory/catalog.column_name') }}</th>
                            <th>{{ trans('admin/inventory/catalog.column_type') }}</th>
                            <th class="text-right">{{ trans('admin/inventory/catalog.column_count') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td>
                                    @if ($entry['category'])
                                        <a href="{{ route('categories.show', $entry['category']->id) }}">{{ $entry['name'] }}</a>
                                    @else
                                        {{ $entry['name'] }}
                                        <span class="text-muted">— {{ trans('admin/inventory/catalog.not_installed') }}</span>
                                    @endif
                                </td>
                                <td>{{ trans('admin/inventory/catalog.type_'.$entry['type']) }}</td>
                                <td class="text-right">{{ $entry['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
