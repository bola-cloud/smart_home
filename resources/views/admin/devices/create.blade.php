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

        <div class="form-group">
            <label for="name">{{ __('lang.name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <div class="form-group">
            <label for="device_type_id">{{ __('lang.device_type') }}</label>
            <select name="device_type_id" class="form-control select2" style="width: 100%;" {{ app()->getLocale() == 'ar' ? 'dir=rtl' : '' }} required>
                <option value="">{{ __('lang.select_device_type') }}</option>
                @foreach($deviceTypes as $deviceType)
                    <option value="{{ $deviceType->id }}">{{ $deviceType->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="number_of_devices">{{ __('lang.number_of_devices') }}</label>
            <input type="number" name="number_of_devices" class="form-control" value="{{ old('number_of_devices', 1) }}" min="1" required>
        </div>

        {{-- <div class="form-group">
            <label for="section_id">{{ __('lang.assign_section') }}</label>
            <select name="section_id" class="form-control select2" style="width: 100%;" {{ app()->getLocale() == 'ar' ? 'dir=rtl' : '' }}>
                <option value="">{{ __('lang.select_section') }}</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}">{{ $section->name }}</option>
                @endforeach
            </select>
        </div> --}}

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
        });
    </script>
@endpush
