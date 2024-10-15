@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <div class="card-header d-flex justify-content-between">
            <h1>@lang('lang.users')</h1>
            <a href="{{ route('users.create') }}" class="btn btn-primary">@lang('lang.create_user')</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>@lang('lang.name')</th>
                    <th>@lang('lang.email')</th>
                    <th>@lang('lang.phone_number')</th>
                    <th>@lang('lang.category')</th>
                    <th>@lang('lang.actions')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone_number }}</td>
                        <td>{{ ucfirst($user->category) }}</td>
                        <td>
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-warning">@lang('lang.edit')</a>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">@lang('lang.delete')</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
