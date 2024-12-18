@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <div class="card-header d-flex justify-content-between">
            <h1>{{ __('lang.Products Management') }}</h1>
            <a href="{{ route('products.create') }}" class="btn btn-primary">{{ __('lang.Create Product') }}</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('lang.Arabic Title') }}</th>
                    <th>{{ __('lang.English Title') }}</th>
                    <th>{{ __('lang.Image') }}</th>
                    <th>{{ __('lang.Price') }} ({{ __('lang.Egypt') }})</th>
                    <th>{{ __('lang.Price') }} ({{ __('lang.Saudi') }})</th>
                    <th>{{ __('lang.Quantity') }}</th>
                    <th>{{ __('lang.Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>{{ $product->ar_title }}</td>
                    <td>{{ $product->en_title }}</td>
                    <td><img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->en_title }}" width="100"></td>
                    
                    <!-- Display Egypt Price -->
                    <td>
                        @php
                            $egyptPrice = $product->prices->where('country', 'Egypt')->first();
                        @endphp
                        @if($egyptPrice)
                            ${{ $egyptPrice->price }}
                        @else
                            {{ __('lang.Not Available') }}
                        @endif
                    </td>

                    <!-- Display Saudi Price -->
                    <td>
                        @php
                            $saudiPrice = $product->prices->where('country', 'Saudi')->first();
                        @endphp
                        @if($saudiPrice)
                            ${{ $saudiPrice->price }}
                        @else
                            {{ __('lang.Not Available') }}
                        @endif
                    </td>

                    <td>{{ $product->quantity }}</td>
                    <td>
                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-warning">{{ __('lang.Edit') }}</a>
                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">{{ __('lang.Delete') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">{{ __('lang.No products found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
