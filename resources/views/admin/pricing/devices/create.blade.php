@extends('layouts.pricing')

@section('content')
<div class="container">
    <h1>Add Device to {{ $room->name }}</h1>

    <!-- Form to Add Device -->
    <form action="{{ route('admin.devices.store', $room->id) }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Device Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" name="quantity" id="quantity" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="unit_price" class="form-label">Unit Price</label>
            <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
