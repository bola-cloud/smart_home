@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <form action="{{ route('admin.dashboard') }}" method="GET" class="d-flex align-items-center">
                <div class="form-group me-2">
                    <label for="from_date">From Date:</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="form-group me-2">
                    <label for="to_date">To Date:</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Total Devices -->
        <div class="col-md-4">
            <div class="card bg-light text-center p-3">
                <h4>Total Devices</h4>
                <p><strong>{{ $totalDevices }}</strong></p>
                <p>Active: {{ $activeDevices }} | Inactive: {{ $inactiveDevices }}</p>
            </div>
        </div>

        <!-- Purchased Products -->
        <div class="col-md-4">
            <div class="card bg-light text-center p-3">
                <h4>Purchased Products</h4>
                <p><strong>{{ $purchasedProducts }}</strong></p>
            </div>
        </div>

        <!-- Total Income -->
        <div class="col-md-4">
            <div class="card bg-light text-center p-3">
                <h4>Total Income</h4>
                <p><strong>${{ number_format($totalIncome, 2) }}</strong></p>
            </div>
        </div>

        <!-- Number of Users -->
        <div class="col-md-4">
            <div class="card bg-light text-center p-3">
                <h4>Users (Category: User)</h4>
                <p><strong>{{ $usersCount }}</strong></p>
            </div>
        </div>

        <!-- Number of Device Types -->
        <div class="col-md-4">
            <div class="card bg-light text-center p-3">
                <h4>Device Types</h4>
                <p><strong>{{ $deviceTypesCount }}</strong></p>
            </div>
        </div>
    </div>
</div>
@endsection
