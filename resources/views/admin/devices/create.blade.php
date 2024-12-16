@extends('layouts.admin')

@section('content')
<div class="card p-4">
    <h1>{{ __('lang.create_device') }}</h1>
    <div class="row">
        @if(session('success'))
            <div class="alert alert-success">{{ __('lang.success_message') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <strong>{{ __('lang.error_heading') }}</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <form action="{{ route('devices.store') }}" method="POST">
        @csrf

        <!-- Name -->
        <div class="form-group">
            <label for="name">{{ __('lang.name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <!-- Device Type -->
        <div class="form-group">
            <label for="device_type_id">{{ __('lang.device_type') }}</label>
            <select name="device_type_id" id="device_type_id" class="form-control select2" style="width: 100%;" {{ app()->getLocale() == 'ar' ? 'dir=rtl' : '' }} required>
                <option value="">{{ __('lang.select_device_type') }}</option>
                @foreach($deviceTypes as $deviceType)
                    <option value="{{ $deviceType->id }}">{{ $deviceType->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Number of Devices -->
        <div class="form-group">
            <label for="number_of_devices">{{ __('lang.number_of_devices') }}</label>
            <input type="number" id="number_of_devices" name="number_of_devices" class="form-control" value="0" min="0" readonly>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">{{ __('lang.submit') }}</button>
    </form>
</div>
@endsection

@push('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
@endpush

@push('js')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Update number_of_devices when a device type is selected
        $('#device_type_id').on('change', function() {
            let deviceTypeId = $(this).val();

            if (deviceTypeId) {
                $.ajax({
                    url: "{{ route('device.channels.count') }}", // Route for counting channels
                    type: "GET",
                    data: { device_type_id: deviceTypeId },
                    success: function(response) {
                        $('#number_of_devices').val(response.channels_count);
                    },
                    error: function(xhr) {
                        alert('Error fetching channel count: ' + xhr.responseText);
                    }
                });
            } else {
                $('#number_of_devices').val(0); // Reset value if no type selected
            }
        });
    });
</script>
@endpush
