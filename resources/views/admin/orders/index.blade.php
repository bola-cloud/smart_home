@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>{{ __('Orders List') }}</h1>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Orders Table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ __('Order ID') }}</th>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Total Amount') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->customer->name }}</td>
                        <td>{{ $order->total_amount }}</td>
                        <td>
                            <span class="badge {{ $order->status == 'completed' ? 'bg-success' : 'bg-warning' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td>
                            @if($order->status !== 'completed')
                                <form action="{{ route('orders.complete', $order->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success">{{ __('Mark as Completed') }}</button>
                                </form>
                            @else
                                <button class="btn btn-secondary" disabled>{{ __('Completed') }}</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="d-flex justify-content-between">
            <div>
                Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} orders.
            </div>
            <div>
                {{ $orders->links() }} <!-- This will display pagination links -->
            </div>
        </div>
    </div>
</div>
@endsection
