@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <div class="card-header d-flex justify-content-between">
            <h1>{{ __('lang.devices') }}</h1>
            <a href="{{ route('devices.create') }}" class="btn btn-primary mb-3">{{ __('lang.create_device') }}</a>
        </div>
        <!-- Filters and Search -->
        <div class="row mb-3">
            <!-- Search by Name -->
            <div class="col-md-4">
                <input type="text" id="search" class="form-control" placeholder="{{ __('lang.search_name') }}">
            </div>
            <!-- Filter by Device Type -->
            <div class="col-md-3">
                <select id="filterType" class="form-control select2">
                    <option value="">{{ __('lang.filter_by_type') }}</option>
                    @foreach($deviceTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Filter by Activation -->
            <div class="col-md-3">
                <select id="filterActivation" class="form-control select2">
                    <option value="">{{ __('lang.filter_by_activation') }}</option>
                    <option value="1">{{ __('lang.active') }}</option>
                    <option value="0">{{ __('lang.inactive') }}</option>
                </select>
            </div>
        </div>

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
                <tbody id="deviceTableBody">
                    @foreach($devices as $device)
                        <tr>
                            <td>{{ $device->name }}</td>
                            <td>{{ $device->deviceType ? $device->deviceType->name : '--' }}</td>
                            <td>{{ $device->serial }}</td>
                            <td>{{ $device->activation ? __('lang.active') : __('lang.inactive') }}</td>
                            <td>{{ $device->updated_at ? $device->updated_at->format('Y-m-d H:i:s') : __('lang.not_available') }}</td>
                            <td>{{ $device->section->name ?? __('lang.not_available') }}</td>
                            <td>
                                <a href="{{ route('devices.edit', $device) }}" class="btn btn-warning">{{ __('lang.edit') }}</a>
                                <form action="{{ route('devices.destroy', $device) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">{{ __('lang.delete') }}</button>
                                </form>
                                <a href="{{ route('devices.show_components', $device->id) }}" class="btn btn-info">{{ __('lang.view_components') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <!-- Pagination -->
            <div id="pagination" class="d-flex justify-content-center mt-3">
                {{ $devices->links('pagination::bootstrap-4') }}
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

            // Function to fetch and update the table
            function fetchDevices() {
                let search = $('#search').val();
                let deviceTypeId = $('#filterType').val();
                let activation = $('#filterActivation').val();

                $.ajax({
                    url: "{{ route('devices.index') }}",
                    type: "GET",
                    data: {
                        search: search,
                        device_type_id: deviceTypeId,
                        activation: activation
                    },
                    success: function (response) {
                        // Update the table body
                        let tbody = '';
                        response.devices.forEach(device => {
                            tbody += `
                                <tr>
                                    <td>${device.name}</td>
                                    <td>${device.device_type?.name ?? '--'}</td>
                                    <td>${device.serial}</td>
                                    <td>${device.activation ? '{{ __('lang.active') }}' : '{{ __('lang.inactive') }}'}</td>
                                    <td>${device.updated_at ?? '{{ __('lang.not_available') }}'}</td>
                                    <td>${device.section?.name ?? '{{ __('lang.not_available') }}'}</td>
                                    <td>
                                        <a href="/devices/${device.id}/edit" class="btn btn-warning">{{ __('lang.edit') }}</a>
                                        <form action="/devices/${device.id}" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">{{ __('lang.delete') }}</button>
                                        </form>
                                        <a href="/devices/${device.id}/components" class="btn btn-info">{{ __('lang.view_components') }}</a>
                                    </td>
                                </tr>
                            `;
                        });

                        $('#deviceTableBody').html(tbody);
                        $('#pagination').html(response.pagination);
                    },
                    error: function (xhr) {
                        alert('Error: ' + xhr.responseText);
                    }
                });
            }

            // Real-time search and filter triggers
            $('#search, #filterType, #filterActivation').on('input change', function () {
                fetchDevices();
            });
        });
    </script>
@endpush
