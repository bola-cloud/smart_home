@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <h1>@lang('lang.create_user')</h1>

        <form action="{{ route('users.store') }}" method="POST">
            @csrf
        
            <div class="form-group">
                <label for="name">@lang('lang.name')</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}">
            </div>
        
            <div class="form-group">
                <label for="email">@lang('lang.email')</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
            </div>
        
            <div class="form-group">
                <label for="phone_number">@lang('lang.phone_number')</label>
                <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number') }}">
            </div>
        
            <div class="form-group">
                <label for="category">@lang('lang.category')</label>
                <select name="category" class="form-control">
                    <option value="admin">@lang('lang.admin')</option>
                    <option value="user">@lang('lang.user')</option>
                    <option value="technical">@lang('lang.technical')</option>
                </select>
            </div>
        
            <div class="form-group">
                <label for="password">@lang('lang.password')</label>
                <input type="password" name="password" class="form-control">
            </div>
        
            <div class="form-group">
                <label for="password_confirmation">@lang('lang.password_confirmation')</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
        
            <button type="submit" class="btn btn-primary">@lang('lang.create_user')</button>
        </form>
    </div>
</div>

@endsection
