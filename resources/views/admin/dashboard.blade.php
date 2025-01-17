@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Central Card -->
    <div class="card shadow-lg p-4">
        <!-- Date Filter Section -->
        <form action="{{ route('admin.dashboard') }}" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <label for="from_date" class="form-label fw-bold">{{ __('lang.from_date') }}</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-6">
                    <label for="to_date" class="form-label fw-bold">{{ __('lang.to_date') }}</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary px-4">{{ __('lang.filter') }}</button>
            </div>
        </form>

        <!-- Statistics Section -->
        <div class="row g-4">
            <!-- Total Devices -->
            <div class="col-md-4">
                <div class="card bg-primary shadow-sm text-center p-4 text-white" style="height: 180px;">
                    <h5 class="fw-bold">{{ __('lang.total_devices') }}</h5>
                    <h2 class="fw-bold">{{ $totalDevices }}</h2>
                    <p>{{ __('lang.active') }}: {{ $activeDevices }} | {{ __('lang.inactive') }}: {{ $inactiveDevices }}</p>
                </div>
            </div>

            <!-- Purchased Products -->
            <div class="col-md-4">
                <div class="card bg-success shadow-sm text-center p-4 text-white" style="height: 180px;">
                    <h5 class="fw-bold">{{ __('lang.purchased_products') }}</h5>
                    <h2 class="fw-bold">{{ $purchasedProducts }}</h2>
                </div>
            </div>

            <!-- Total Income -->
            <div class="col-md-4">
                <div class="card bg-danger shadow-sm text-center p-4 text-white" style="height: 180px;">
                    <h5 class="fw-bold">{{ __('lang.total_income') }}</h5>
                    <h2 class="fw-bold">${{ number_format($totalIncome, 2) }}</h2>
                </div>
            </div>

            <!-- Users (Category: User) -->
            <div class="col-md-4">
                <div class="card bg-info shadow-sm text-center p-4 text-white" style="height: 180px;">
                    <h5 class="fw-bold">{{ __('lang.users') }}</h5>
                    <h2 class="fw-bold">{{ $usersCount }}</h2>
                </div>
            </div>

            <!-- Device Types -->
            <div class="col-md-4">
                <div class="card bg-warning shadow-sm text-center p-4 text-dark" style="height: 180px;">
                    <h5 class="fw-bold">{{ __('lang.device_types') }}</h5>
                    <h2 class="fw-bold">{{ $deviceTypesCount }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
