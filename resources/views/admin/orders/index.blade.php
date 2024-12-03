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

        <!-- Search and Filter Form -->
        <form method="GET" action="{{ route('checkouts.index') }}">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" name="search_code" class="form-control" placeholder="Search by code" value="{{ request('search_code') }}">
                </div>
                <div class="col-md-4">
                    <select name="user_id" class="form-control select2" style="width: 100%">
                        <option value="">Select a user</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">{{ __('lang.search') }}</button>
                </div>
            </div>
        </form>

        <!-- Display Checkouts -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ __('lang.id') }}</th>
                    <th>{{ __('lang.code') }}</th>
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
                        <td>{{ $checkout->code }}</td>
                        <td>{{ $checkout->user->name }}</td>
                        <td>{{ $checkout->total_amount }}</td>
                        @if ($checkout->status == 'failed')
                            <td> <span class="badge bg-danger">{{ $checkout->status }}</span></td>
                        @elseif ($checkout->status == 'completed')
                            <td> <span class="badge bg-success">{{ $checkout->status }}</span></td>
                        @else
                            <td> <span class="badge bg-warning">{{ $checkout->status }}</span></td>
                        @endif
                        <td>{{ $checkout->address }}</td>
                        <td>
                            <!-- Form to change order status to 'Completed' -->
                            @if($checkout->status != 'completed')
                                <form action="{{ route('checkouts.updateStatus', [$checkout->id, 'completed']) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-success">{{ __('lang.mark_as_complete') }}</button>
                                </form>
                            @endif
                        
                            <!-- Form to change order status to 'Failed' -->
                            @if($checkout->status != 'failed')
                                <form action="{{ route('checkouts.updateStatus', [$checkout->id, 'failed']) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-danger">{{ __('lang.mark_as_failed') }}</button>
                                </form>
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
@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2 for user selection dropdown
            $('.select2').select2({
                placeholder: "Select a user"
            });
        });
    </script>
@endpush