@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <div class="card-header d-flex justify-content-between">
            <h1>{{ __('lang.devices') }}</h1>
            <a href="{{ route('devices.create') }}" class="btn btn-primary mb-3">{{ __('lang.create_device') }}</a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('lang.name') }}</th>
                        <th>{{ __('lang.device_type') }}</th>
                        <th>{{ __('lang.activation') }}</th>
                        <th>{{ __('lang.last_updated') }}</th>
                        <th>{{ __('lang.section') }}</th>
                        <th>{{ __('lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($devices as $device)
                        <tr>
                            <td>{{ $device->name }}</td>
                            <td>{{ $device->deviceType->name }}</td> <!-- Reference device type -->
                            <td>{{ $device->activation ? __('lang.active') : __('lang.inactive') }}</td> <!-- Display activation status -->
                            <td>{{ $device->last_updated ? $device->last_updated->format('Y-m-d H:i:s') : __('lang.not_available') }}</td>
                            <td>{{ $device->section->name ?? __('lang.not_available') }}</td> <!-- Nullable section -->
                            <td>
                                <a href="{{ route('devices.edit', $device) }}" class="btn btn-warning">{{ __('lang.edit') }}</a>
                                <form action="{{ route('devices.destroy', $device) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">{{ __('lang.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
