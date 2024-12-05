@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>{{ __('lang.update_shipping') }}</h1>

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

        <!-- Success Message -->
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Form to Update Shipping Values -->
        <form action="{{ route('shipping.update') }}" method="POST">
            @csrf

            <!-- Cities (Egypt) -->
            <h3>{{ __('lang.cities_shipping') }}</h3>
            @foreach($cities as $city)
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="city_{{ $city->id }}">{{ $city->city_name_en }}</label>
                        <input type="hidden" name="cities[{{ $city->id }}][id]" value="{{ $city->id }}">
                        <select name="cities[{{ $city->id }}][shipping]" class="form-control">
                            <option value="0" {{ $city->shipping == 0 ? 'selected' : '' }}>{{ __('lang.no_shipping') }}</option>
                            <option value="1" {{ $city->shipping == 1 ? 'selected' : '' }}>{{ __('lang.with_shipping') }}</option>
                        </select>
                    </div>
                </div>
            @endforeach

            <!-- Districts Lite (Saudi Arabia) -->
            <h3>{{ __('lang.districts_shipping') }}</h3>
            @foreach($districts as $district)
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="district_{{ $district->id }}">{{ $district->district_name }}</label>
                        <input type="hidden" name="districts_lite[{{ $district->id }}][id]" value="{{ $district->id }}">
                        <select name="districts_lite[{{ $district->id }}][shipping]" class="form-control">
                            <option value="0" {{ $district->shipping == 0 ? 'selected' : '' }}>{{ __('lang.no_shipping') }}</option>
                            <option value="1" {{ $district->shipping == 1 ? 'selected' : '' }}>{{ __('lang.with_shipping') }}</option>
                        </select>
                    </div>
                </div>
            @endforeach

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __('lang.save_changes') }}</button>
        </form>
    </div>
</div>
@endsection

@push('js')
    <!-- Include any additional scripts if needed -->
@endpush
