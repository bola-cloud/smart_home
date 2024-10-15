@extends('layouts.admin')

@section('content')
<div class="card p-3">
    <h1>{{ __('lang.edit_project') }}</h1>

    <form action="{{ route('projects.update', $project) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">{{ __('lang.name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $project->name) }}" required>
        </div>

        <div class="form-group">
            <label for="description">{{ __('lang.description') }}</label>
            <textarea name="description" class="form-control">{{ old('description', $project->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="user_id">{{ __('lang.assign_user') }}</label>
            <select name="user_id" class="form-control select2" style="width: 100%;" {{ app()->getLocale() == 'ar' ? 'dir=rtl' : '' }} required>
                <option value="">{{ __('lang.select_user') }}</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $project->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('lang.submit') }}</button>
    </form>
</div>
@endsection

@push('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
@endpush

@push('js')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%' // Make the Select2 dropdown full width
            });
        });
    </script>
@endpush