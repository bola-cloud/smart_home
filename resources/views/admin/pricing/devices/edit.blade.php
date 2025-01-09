@extends('layouts.pricing')

@section('content')
<div class="container">
    <h1>Edit Device: {{ $device->name }}</h1>

    <!-- Form to Edit Device -->
    <form action="{{ route('pricing.devices.update', $device->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Device Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $device->name }}" required>
        </div>

        <div class="mb-3">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" name="quantity" id="quantity" class="form-control" value="{{ $device->quantity }}" required min="1">
        </div>

        <div class="mb-3">
            <label for="unit_price" class="form-label">Unit Price</label>
            <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control" value="{{ $device->unit_price }}" required min="0">
        </div>

        <button type="submit" class="btn btn-success">Update Device</button>
        <a href="{{ route('pricing.devices.index', $device->room_id) }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
