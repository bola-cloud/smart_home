@extends('layouts.pricing')

@section('content')
<div class="container">
    <h1 class="mb-4">Edit Room</h1>

    <!-- Form to Edit Room -->
    <form action="{{ route('pricing.rooms.update', $room->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Room Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $room->name }}" required>
        </div>
        <button type="submit" class="btn btn-warning">Update</button>
    </form>
</div>
@endsection
