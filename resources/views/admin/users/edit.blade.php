@extends('layouts.admin')

@section('content')
<h1>Edit User</h1>

<form action="{{ route('admin.users.update', $user) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="form-group">
        <label for="name">@lang('users.name')</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}">
    </div>

    <div class="form-group">
        <label for="email">@lang('users.email')</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
    </div>

    <div class="form-group">
        <label for="phone_number">@lang('users.phone_number')</label>
        <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $user->phone_number) }}">
    </div>

    <div class="form-group">
        <label for="category">@lang('users.category')</label>
        <select name="category" class="form-control">
            <option value="admin" {{ $user->category == 'admin' ? 'selected' : '' }}>@lang('users.admin')</option>
            <option value="user" {{ $user->category == 'user' ? 'selected' : '' }}>@lang('users.user')</option>
            <option value="technical" {{ $user->category == 'technical' ? 'selected' : '' }}>@lang('users.technical')</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">@lang('users.update_user')</button>
</form>
@endsection
