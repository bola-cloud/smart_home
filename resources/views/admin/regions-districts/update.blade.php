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
            <div class="col-md-6">
                <button class="btn btn-success mt-4" data-bs-toggle="modal" data-bs-target="#createRegionModal">{{ __('lang.create_region') }}</button>
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
            <div class="col-md-6">
                <button class="btn btn-success mt-4" id="addCityBtn" disabled data-bs-toggle="modal" data-bs-target="#createCityModal">{{ __('lang.add_city') }}</button>
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
            <div class="col-md-6">
                <button class="btn btn-success mt-4" id="addDistrictBtn" disabled data-bs-toggle="modal" data-bs-target="#createDistrictModal">{{ __('lang.add_district') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal to Create Region -->
<div class="modal fade" id="createRegionModal" tabindex="-1" aria-labelledby="createRegionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createRegionModalLabel">{{ __('lang.create_region') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createRegionForm">
                    @csrf
                    <div class="mb-3">
                        <label for="name_en">{{ __('lang.name_en') }}</label>
                        <input type="text" class="form-control" name="name_en" required>
                    </div>
                    <div class="mb-3">
                        <label for="name_ar">{{ __('lang.name_ar') }}</label>
                        <input type="text" class="form-control" name="name_ar" required>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('lang.save_changes') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modals to Add City and District -->
<div class="modal fade" id="createCityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.add_city') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createCityForm">
                    @csrf
                    <div class="mb-3">
                        <label for="city_name">{{ __('lang.city_name') }}</label>
                        <input type="text" class="form-control" name="city_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('lang.save_changes') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createDistrictModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.add_district') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createDistrictForm">
                    @csrf
                    <div class="mb-3">
                        <label for="district_name">{{ __('lang.district_name') }}</label>
                        <input type="text" class="form-control" name="district_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('lang.save_changes') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
    $(document).ready(function () {
        $('.select2').select2();

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
                        $('#addCityBtn').prop('disabled', false);
                        response.cities.forEach(function (city) {
                            $('#city').append(`<option value="${city.city_id}">${city.name_en}</option>`);
                        });
                    }
                });
            }
        });

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
                        $('#addDistrictBtn').prop('disabled', false);
                        response.districts.forEach(function (district) {
                            $('#district').append(`<option value="${district.district_id}">${district.name_en}</option>`);
                        });
                    }
                });
            }
        });

        $('#createRegionForm').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: "{{ route('regions.store') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function () {
                    location.reload();
                }
            });
        });
    });
</script>
@endpush
