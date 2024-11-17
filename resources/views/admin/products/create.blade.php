@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>{{ __('Create Product') }}</h1>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="title">{{ __('Title') }}</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="small_description">{{ __('Small Description') }}</label>
                <input type="text" name="small_description" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="description">{{ __('Description') }}</label>
                <textarea name="description" class="form-control"></textarea>
            </div>

            <div class="form-group">
                <label for="image">{{ __('Image') }}</label>
                <input type="file" name="image" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="price">{{ __('Price') }}</label>
                <input type="number" name="price" class="form-control" min="0">
            </div>

            <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
        </form>
    </div>
</div>
@endsection
