@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <div class="card-header d-flex justify-content-between">
            <h1>{{ __('lang.devices') }}</h1>
            <a href="{{ route('devices.create') }}" class="btn btn-primary mb-3">{{ __('lang.create_device') }}</a>
        </div>
        <!-- Filters and Search -->
        <form method="GET" action="{{ route('devices.index') }}" class="mb-3">
            <div class="row">
                <!-- Search by Name -->
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('lang.search_name') }}"
                        value="{{ request('search') }}">
                </div>
                <!-- Filter by Device Type -->
                <div class="col-md-3">
                    <select name="device_type_id" class="form-control select2">
                        <option value="">{{ __('lang.filter_by_type') }}</option>
                        @foreach($deviceTypes as $type)
                            <option value="{{ $type->id }}" {{ request('device_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <!-- Filter by Activation -->
                <div class="col-md-3">
                    <select name="activation" class="form-control select2">
                        <option value="">{{ __('lang.filter_by_activation') }}</option>
                        <option value="1" {{ request('activation') === '1' ? 'selected' : '' }}>
                            {{ __('lang.active') }}
                        </option>
                        <option value="0" {{ request('activation') === '0' ? 'selected' : '' }}>
                            {{ __('lang.inactive') }}
                        </option>
                    </select>
                </div>
                <!-- Filter Button -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary">{{ __('lang.filter') }}</button>
                </div>
            </div>
        </form>

        <!-- Device Table -->
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('lang.name') }}</th>
                        <th>{{ __('lang.device_type') }}</th>
                        <th>{{ __('lang.device_serial') }}</th>
                        <th>{{ __('lang.activation') }}</th>
                        <th>{{ __('lang.last_updated') }}</th>
                        <th>{{ __('lang.section') }}</th>
                        <th>{{ __('lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                        <tr>
                            <td>{{ $device->name }}</td>
                            <td>{{ $device->deviceType ? $device->deviceType->name : '--' }}</td>
                            <td>{{ $device->serial }}</td>
                            <td>{{ $device->activation ? __('lang.active') : __('lang.inactive') }}</td>
                            <td>
                                {{ $device->updated_at ? $device->updated_at->format('Y-m-d H:i:s') : __('lang.not_available') }}
                            </td>
                            <td>{{ $device->section->name ?? __('lang.not_available') }}</td>
                            <td>
                                <a href="{{ route('devices.edit', $device) }}" class="btn btn-warning">{{ __('lang.edit') }}</a>
                                <form action="{{ route('devices.destroy', $device) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">{{ __('lang.delete') }}</button>
                                </form>
                                <a href="{{ route('devices.show_components', $device->id) }}" class="btn btn-info">
                                    {{ __('lang.view_components') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('lang.no_devices_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <!-- Pagination Links -->
            <div class="d-flex justify-content-center mt-3">
                {{ $devices->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.select2').select2();
        });
    </script>
@endpush
