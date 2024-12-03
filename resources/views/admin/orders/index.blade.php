<!-- resources/views/admin/checkouts/index.blade.php -->

@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>{{ __('messages.checkouts_list') }}</h1>

        <!-- Displaying Errors -->
        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Display Checkouts -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ __('messages.id') }}</th>
                    <th>{{ __('messages.customer_name') }}</th>
                    <th>{{ __('messages.total_amount') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.address') }}</th>
                    <th>{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($checkouts as $checkout)
                    <tr>
                        <td>{{ $checkout->id }}</td>
                        <td>{{ $checkout->user->name }}</td>
                        <td>{{ $checkout->total_amount }}</td>
                        <td>{{ ucfirst($checkout->status) }}</td>
                        <td>{{ $checkout->address }}</td>
                        <td>
                            <!-- Form to change order status -->
                            @if($checkout->status != 'completed')
                                <form action="{{ route('checkouts.updateStatus', $checkout->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-success">{{ __('messages.mark_as_complete') }}</button>
                                </form>
                            @else
                                <span class="badge bg-success">{{ __('messages.completed') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="d-flex justify-content-center">
            {{ $checkouts->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
