@extends('layouts.pricing')

@section('content')
<div class="container">
    <h1 class="mb-4">Add New Room</h1>

    <!-- Form to Add Room -->
    <form action="{{ route('pricing.rooms.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Room Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
