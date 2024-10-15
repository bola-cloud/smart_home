@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <div class="card-header d-flex justify-content-between">
            <h1>{{ __('lang.device_types') }}</h1>  
            <a href="{{ route('device_types.create') }}" class="btn btn-primary">{{ __('lang.create_device_type') }}</a>
        </div>
        <table class="table mt-3">
            <thead>
                <tr>
                    <th>{{ __('lang.device_type_name') }}</th>
                    <th>{{ __('lang.channel') }}</th>
                    <th>{{ __('lang.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deviceTypes as $deviceType)
                    <tr>
                        <td>{{ $deviceType->name }}</td>
                        <td>
                            <ul>
                                @foreach($deviceType->channels as $channel)
                                    <li>{{ $channel->name }} ({{ __('lang.order') }}: {{ $channel->order }})</li>
                                @endforeach
                            </ul>
                        </td>
                        <td>
                            <a href="{{ route('device_types.edit', $deviceType) }}" class="btn btn-warning">{{ __('lang.edit') }}</a>
                            <form action="{{ route('device_types.destroy', $deviceType) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">{{ __('lang.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
