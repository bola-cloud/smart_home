@extends('layouts.pricing')

@section('content')
<div class="container">
    <h1>Devices in {{ $room->name }}</h1>

    <!-- Success Message -->
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Add Device Button -->
    <a href="{{ route('admin.devices.create', $room->id) }}" class="btn btn-primary mb-3">Add New Device</a>

    <!-- Devices Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($devices as $device)
            <tr>
                <td>{{ $device->id }}</td>
                <td>{{ $device->name }}</td>
                <td>{{ $device->quantity }}</td>
                <td>{{ $device->unit_price }}</td>
                <td>{{ $device->total_price }}</td>
                <td>
                    <form action="{{ route('admin.devices.destroy', $device->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
