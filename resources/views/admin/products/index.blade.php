@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <div class="card-header d-flex justify-content-between">
            <h1>{{ __('Products Management') }}</h1>
            <a href="{{ route('products.create') }}" class="btn btn-primary">{{ __('Create Product') }}</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Title') }}</th>
                    <th>{{ __('Small Description') }}</th>
                    <th>{{ __('Image') }}</th>
                    <th>{{ __('Price') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>{{ $product->title }}</td>
                    <td>{{ $product->small_description }}</td>
                    <td><img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->title }}" width="100"></td>
                    <td>${{ $product->price }}</td>
                    <td>
                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-warning">{{ __('Edit') }}</a>
                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">{{ __('No products found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
