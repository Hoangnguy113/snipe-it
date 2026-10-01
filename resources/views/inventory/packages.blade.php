@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/deploy.packages_title') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                <form method="POST" action="{{ route('inventory.packages.store') }}" enctype="multipart/form-data" class="form-horizontal">
                    @csrf
                    <div class="form-group"><label class="col-md-2 control-label">{{ trans('admin/inventory/deploy.name') }}</label><div class="col-md-6"><input type="text" name="name" class="form-control" required></div></div>
                    <div class="form-group"><label class="col-md-2 control-label">{{ trans('admin/inventory/deploy.version') }}</label><div class="col-md-3"><input type="text" name="version" class="form-control"></div></div>
                    <div class="form-group"><label class="col-md-2 control-label">{{ trans('admin/inventory/deploy.file') }}</label><div class="col-md-6"><input type="file" name="package" class="form-control" required></div></div>
                    <div class="form-group"><label class="col-md-2 control-label">{{ trans('admin/inventory/deploy.install_cmd') }}</label><div class="col-md-6"><input type="text" name="install_cmd" class="form-control" placeholder="msiexec /i office.msi /qn" required></div></div>
                    <div class="form-group"><div class="col-md-offset-2 col-md-6"><button class="btn btn-primary">{{ trans('general.save') }}</button></div></div>
                </form>
                <table class="table table-striped">
                    <thead><tr><th>{{ trans('admin/inventory/deploy.name') }}</th><th>{{ trans('admin/inventory/deploy.version') }}</th><th>SHA512</th><th>{{ trans('admin/inventory/deploy.size') }}</th></tr></thead>
                    <tbody>
                        @foreach ($packages as $p)
                            <tr><td>{{ $p->name }}</td><td>{{ $p->version }}</td><td><small>{{ substr($p->sha512, 0, 16) }}…</small></td><td>{{ number_format($p->filesize) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
