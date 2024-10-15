@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <h1>{{ __('lang.edit_device_type_with_channels') }}</h1>

        <form id="device-type-form" action="{{ route('device_types.update', $deviceType->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="device_type_name">{{ __('lang.device_type_name') }}</label>
                <input type="text" name="device_type_name" class="form-control" value="{{ old('device_type_name', $deviceType->name) }}" required>
            </div>

            <div class="form-group">
                <label for="number_of_channels">{{ __('lang.number_of_channels') }}</label>
                <input type="number" id="number_of_channels" name="number_of_channels" class="form-control" value="{{ old('number_of_channels', $deviceType->channels->count()) }}" min="1" required>
            </div>

            <!-- Container for dynamically added channels -->
            <div id="channel-fields-container">
                <ul id="sortable-channel-list" class="list-group">
                    @foreach($deviceType->channels as $index => $channel)
                        <li class="list-group-item border p-3 mt-3 mb-3" data-id="{{ $channel->id }}">
                            <h5>{{ __('lang.channel') }} {{ $index + 1 }}</h5>
                            <div class="form-group">
                                <label for="channel_name_{{ $index }}">{{ __('lang.channel_name') }} {{ $index + 1 }}</label>
                                <input type="text" name="channels[{{ $index + 1 }}][name]" class="form-control" value="{{ old('channels.' . $index . '.name', $channel->name) }}" required>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <button type="submit" class="btn btn-primary mt-3">{{ __('lang.submit') }}</button>
        </form>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.13.0/Sortable.min.js"></script>

<script>
    function updateChannelNames() {
        const items = document.querySelectorAll('#sortable-channel-list li');
        items.forEach((item, index) => {
            const label = item.querySelector('h5');
            const inputLabel = item.querySelector('label');
            const input = item.querySelector('input');

            // Update the channel name dynamically
            label.textContent = `{{ __('lang.channel') }} ${index + 1}`;
            inputLabel.textContent = `{{ __('lang.channel_name') }} ${index + 1}`;
            input.name = `channels[${index + 1}][name]`;
        });
    }

    // Initialize sortable on the channel list
    new Sortable(document.getElementById('sortable-channel-list'), {
        animation: 150,
        onEnd: updateChannelNames
    });

    // Update fields based on number of channels entered
    document.getElementById('number_of_channels').addEventListener('input', function() {
        const numberOfChannels = this.value;
        const container = document.getElementById('sortable-channel-list');
        container.innerHTML = ''; // Clear previous fields

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

        // Reinitialize sortable after updating the number of channels
        new Sortable(document.getElementById('sortable-channel-list'), {
            animation: 150,
            onEnd: updateChannelNames
        });
    });
</script>
@endpush
