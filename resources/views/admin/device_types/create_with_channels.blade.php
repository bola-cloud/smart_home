@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <h1>{{ __('lang.create_device_type_with_channels') }}</h1>

        <form id="device-type-form" action="{{ route('device_types.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="device_type_name">{{ __('lang.device_type_name') }}</label>
                <input type="text" name="device_type_name" class="form-control" value="{{ old('device_type_name') }}" required>
            </div>

            <div class="form-group">
                <label for="number_of_channels">{{ __('lang.number_of_channels') }}</label>
                <input type="number" id="number_of_channels" name="number_of_channels" class="form-control" min="1" value="1" required>
            </div>

            <!-- Container for dynamically added channels -->
            <div id="channel-fields-container"></div>

            <input type="hidden" id="sorted_channel_order" name="sorted_channel_order">

            <button type="submit" class="btn btn-primary mt-3">{{ __('lang.submit') }}</button>
        </form>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.13.0/Sortable.min.js"></script>

<script>
    function generateChannelFields(numberOfChannels) {
        const container = document.getElementById('channel-fields-container');
        container.innerHTML = ''; // Clear previous fields

        // Create input fields for each channel
        for (let i = 1; i <= numberOfChannels; i++) {
            const channelFields = `
                <li class="list-group-item border p-3 mt-3 mb-3" data-id="${i}">
                    <h5>{{ __('lang.channel') }} ${i}</h5>
                    <div class="form-group">
                        <label for="channel_name_${i}">{{ __('lang.channel_name') }} ${i}</label>
                        <input type="text" name="channels[${i}][name]" class="form-control" required>
                    </div>
                </li>
            `;
            container.insertAdjacentHTML('beforeend', channelFields);
        }

        // Initialize sortable for the new elements
        new Sortable(document.getElementById('channel-fields-container'), {
            animation: 150,
            onEnd: updateChannelOrder
        });
    }

    function updateChannelOrder() {
        const items = [...document.querySelectorAll('#channel-fields-container li')];
        const sortedOrder = items.map((item, index) => {
            return { id: item.getAttribute('data-id'), order: index + 1 };
        });
        document.getElementById('sorted_channel_order').value = JSON.stringify(sortedOrder);
    }

    // Initial load for one channel
    document.addEventListener('DOMContentLoaded', function() {
        generateChannelFields(1); // Default to 1 channel
    });

    // Update fields based on number of channels entered
    document.getElementById('number_of_channels').addEventListener('input', function() {
        const numberOfChannels = this.value;
        generateChannelFields(numberOfChannels);
    });
</script>
@endpush
