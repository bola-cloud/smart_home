@extends('layouts.admin')

@section('content')
<div class="card p-4">
    <h1>{{ __('lang.add_components_for_device') }}: {{ $device->name }}</h1>
    
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

    <form action="{{ route('components.store_for_device', $device->id) }}" method="POST">
        @csrf

        <!-- Device Info -->
        <input type="hidden" name="device_id" value="{{ $device->id }}">

        <!-- Display Channels and Component Fields -->
        <div class="mt-3">
            @foreach($device->deviceType->channels as $channel)
                <div class="row mb-4">
                    <!-- Left Column: Channel Details -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('lang.channel_name') }}</label>
                            <input type="text" class="form-control" value="{{ $channel->name }}" readonly>
                        </div>
                        <div class="form-group">
                            <label>{{ __('lang.channel_order') }}</label>
                            <input type="text" class="form-control" value="{{ $channel->order }}" readonly>
                        </div>
                    </div>

                    <!-- Right Column: Component Input for Channel -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('lang.component_for_channel') }}: {{ $channel->name }}</label>
                            <input type="text" name="components[{{ $channel->id }}][name]" class="form-control" placeholder="{{ __('lang.component_name') }}" required>

                            <!-- Hidden input to pass the channel order to the component -->
                            <input type="hidden" name="components[{{ $channel->id }}][order]" value="{{ $channel->order }}">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary">{{ __('lang.submit') }}</button>
    </form>
</div>
@endsection
