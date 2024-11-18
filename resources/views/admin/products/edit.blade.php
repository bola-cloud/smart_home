@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>{{ __('lang.Edit Product') }}</h1>

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
                <label for="ar_title">{{ __('lang.Arabic Title') }}</label>
                <input type="text" name="ar_title" class="form-control" value="{{ $product->ar_title }}" required>
            </div>

            <div class="form-group">
                <label for="en_title">{{ __('lang.English Title') }}</label>
                <input type="text" name="en_title" class="form-control" value="{{ $product->en_title }}" required>
            </div>

            <div class="form-group">
                <label for="ar_small_description">{{ __('lang.Arabic Small Description') }}</label>
                <input type="text" name="ar_small_description" class="form-control" value="{{ $product->ar_small_description }}" required>
            </div>

            <div class="form-group">
                <label for="en_small_description">{{ __('lang.English Small Description') }}</label>
                <input type="text" name="en_small_description" class="form-control" value="{{ $product->en_small_description }}" required>
            </div>

            <div class="form-group">
                <label for="ar_description">{{ __('lang.Arabic Description') }}</label>
                <textarea name="ar_description" class="form-control">{{ $product->ar_description }}</textarea>
            </div>

            <div class="form-group">
                <label for="en_description">{{ __('lang.English Description') }}</label>
                <textarea name="en_description" class="form-control">{{ $product->en_description }}</textarea>
            </div>

            <div class="form-group">
                <label for="image">{{ __('lang.Image') }}</label>
                <input type="file" name="image" class="form-control">
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->en_title }}" width="100">
            </div>

            <div class="form-group">
                <label for="price">{{ __('lang.Price') }}</label>
                <input type="number" name="price" class="form-control" min="0" value="{{ $product->price }}">
            </div>

            <button type="submit" class="btn btn-primary">{{ __('lang.Update') }}</button>
        </form>
    </div>
</div>
@endsection
