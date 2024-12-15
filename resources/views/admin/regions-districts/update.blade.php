@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>{{ __('lang.update_shipping') }}</h1>

        <!-- Display Errors -->
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

            <!-- Regions Select -->
            <h3>{{ __('lang.select_region') }}</h3>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="region">{{ __('lang.region') }}</label>
                    <select id="region" name="region_id" class="form-control select2">
                        <option value="">{{ __('lang.select_region') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->region_id }}">{{ $region->name_en }} ({{ $region->name_ar }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Cities Select -->
            <h3>{{ __('lang.select_city') }}</h3>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="city">{{ __('lang.city') }}</label>
                    <select id="city" name="city_id" class="form-control select2" disabled>
                        <option value="">{{ __('lang.select_city') }}</option>
                    </select>
                </div>
            </div>

            <!-- Districts Select -->
            <h3>{{ __('lang.select_district') }}</h3>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="district">{{ __('lang.district') }}</label>
                    <select id="district" name="district_id" class="form-control select2" disabled>
                        <option value="">{{ __('lang.select_district') }}</option>
                    </select>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __('lang.save_changes') }}</button>
        </form>
    </div>
</div>
@endsection

@push('js')
 <!-- Select2 JS -->
 <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
 <!-- Bootstrap JS -->
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2();

        // Fetch Cities Based on Region
        $('#region').on('change', function () {
            let regionId = $(this).val();
            $('#city').prop('disabled', true).html('<option value="">{{ __("lang.select_city") }}</option>');
            $('#district').prop('disabled', true).html('<option value="">{{ __("lang.select_district") }}</option>');

            if (regionId) {
                $.ajax({
                    url: "{{ route('fetch.cities') }}",
                    type: "GET",
                    data: { region_id: regionId },
                    success: function (response) {
                        $('#city').prop('disabled', false);
                        response.cities.forEach(function (city) {
                            $('#city').append(`<option value="${city.city_id}">${city.name_en} (${city.name_ar})</option>`);
                        });
                    }
                });
            }
        });

        // Fetch Districts Based on City
        $('#city').on('change', function () {
            let cityId = $(this).val();
            $('#district').prop('disabled', true).html('<option value="">{{ __("lang.select_district") }}</option>');

            if (cityId) {
                $.ajax({
                    url: "{{ route('fetch.districts') }}",
                    type: "GET",
                    data: { city_id: cityId },
                    success: function (response) {
                        $('#district').prop('disabled', false);
                        response.districts.forEach(function (district) {
                            $('#district').append(`<option value="${district.district_id}">${district.name_en} (${district.name_ar})</option>`);
                        });
                    }
                });
            }
        });
    });
</script>
@endpush
