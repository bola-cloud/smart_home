@extends('layouts.pricing')

@section('content')
<div class="container">
    <h1 class="mb-4">Manage Rooms</h1>

    <!-- Success Message -->
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Add Room Button -->
    <a href="{{ route('pricing.rooms.create') }}" class="btn btn-primary mb-3">Add New Room</a>

    <!-- Room List Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rooms as $room)
            <tr>
                <td>{{ $room->id }}</td>
                <td>{{ $room->name }}</td>
                <td>
                    <a href="{{ route('pricing.rooms.edit', $room->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <a href="{{ route('pricing.devices.index', $room->id) }}" class="btn btn-info btn-sm">Manage Devices</a>
                    <form action="{{ route('pricing.rooms.destroy', $room->id) }}" method="POST" style="display:inline;">
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
