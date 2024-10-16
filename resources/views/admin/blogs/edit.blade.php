@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>{{ __('lang.edit_blog') }}</h1>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('blogs.update', $blog->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title">{{ __('lang.title') }}</label>
                <input type="text" name="title" class="form-control" value="{{ $blog->title }}" required>
            </div>

            <div class="form-group">
                <label for="image">{{ __('lang.image') }}</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <img src="{{ asset('storage/' . $blog->image) }}" alt="{{ $blog->title }}" width="100">
            </div>

            <div class="form-group">
                <label for="description">{{ __('lang.description') }}</label>
                <textarea name="description" class="form-control">{{ $blog->description }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('lang.update') }}</button>
        </form>
    </div>

</div>
@endsection
