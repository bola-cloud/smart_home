@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>{{ __('Edit Product') }}</h1>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title">{{ __('Title') }}</label>
                <input type="text" name="title" class="form-control" value="{{ $product->title }}" required>
            </div>

            <div class="form-group">
                <label for="small_description">{{ __('Small Description') }}</label>
                <input type="text" name="small_description" class="form-control" value="{{ $product->small_description }}" required>
            </div>

            <div class="form-group">
                <label for="description">{{ __('Description') }}</label>
                <textarea name="description" class="form-control">{{ $product->description }}</textarea>
            </div>

            <div class="form-group">
                <label for="image">{{ __('Image') }}</label>
                <input type="file" name="image" class="form-control">
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->title }}" width="100">
            </div>

            <div class="form-group">
                <label for="price">{{ __('Price') }}</label>
                <input type="number" name="price" class="form-control" min="0" value="{{ $product->price }}">
            </div>

            <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
        </form>
    </div>
</div>
@endsection
