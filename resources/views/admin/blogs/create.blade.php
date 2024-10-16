@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">

        <h1 class="mb-3">{{ __('lang.create_blog') }}</h1>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('blogs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="title">{{ __('lang.title') }}</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="image">{{ __('lang.image') }}</label>
                <input type="file" name="image" accept="image/*" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="description">{{ __('lang.description') }}</label>
                <textarea name="description" class="form-control"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('lang.create') }}</button>
        </form>
    </div>
</div>
@endsection
