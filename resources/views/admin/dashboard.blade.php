@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Central Card -->
    <div class="card shadow-lg p-4">
        <!-- Date Filter Section -->
        <form action="{{ route('admin.dashboard') }}" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <label for="from_date" class="form-label fw-bold">From Date:</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-6">
                    <label for="to_date" class="form-label fw-bold">To Date:</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary px-4">Filter</button>
            </div>
        </form>

        <!-- Statistics Section -->
        <div class="row g-4">
            <!-- Total Devices -->
            <div class="col-md-4">
                <div class="card bg-light shadow-sm text-center p-4">
                    <h5 class="fw-bold">Total Devices</h5>
                    <h2 class="text-primary fw-bold">{{ $totalDevices }}</h2>
                    <p>Active: {{ $activeDevices }} | Inactive: {{ $inactiveDevices }}</p>
                </div>
            </div>

            <!-- Purchased Products -->
            <div class="col-md-4">
                <div class="card bg-light shadow-sm text-center p-4">
                    <h5 class="fw-bold">Purchased Products</h5>
                    <h2 class="text-success fw-bold">{{ $purchasedProducts }}</h2>
                </div>
            </div>

            <!-- Total Income -->
            <div class="col-md-4">
                <div class="card bg-light shadow-sm text-center p-4">
                    <h5 class="fw-bold">Total Income</h5>
                    <h2 class="text-danger fw-bold">${{ number_format($totalIncome, 2) }}</h2>
                </div>
            </div>

            <!-- Users (Category: User) -->
            <div class="col-md-4">
                <div class="card bg-light shadow-sm text-center p-4">
                    <h5 class="fw-bold">Users (Category: User)</h5>
                    <h2 class="text-info fw-bold">{{ $usersCount }}</h2>
                </div>
            </div>

            <!-- Device Types -->
            <div class="col-md-4">
                <div class="card bg-light shadow-sm text-center p-4">
                    <h5 class="fw-bold">Device Types</h5>
                    <h2 class="text-warning fw-bold">{{ $deviceTypesCount }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
