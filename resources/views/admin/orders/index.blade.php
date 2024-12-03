<!-- resources/views/admin/checkouts/index.blade.php -->

@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>{{ __('lang.checkouts_list') }}</h1>

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
                    <th>{{ __('lang.id') }}</th>
                    <th>{{ __('lang.customer_name') }}</th>
                    <th>{{ __('lang.total_amount') }}</th>
                    <th>{{ __('lang.status') }}</th>
                    <th>{{ __('lang.address') }}</th>
                    <th>{{ __('lang.actions') }}</th>
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
                                    <button type="submit" class="btn btn-success">{{ __('lang.mark_as_complete') }}</button>
                                </form>
                            @else
                                <span class="badge bg-success">{{ __('lang.completed') }}</span>
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
