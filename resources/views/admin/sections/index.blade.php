@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <div class="card-header d-flex justify-content-between">
            <h1>{{ __('lang.sections') }}</h1>

            <a href="{{ route('sections.create') }}" class="btn btn-primary mb-3">{{ __('lang.create_section') }}</a>     
        </div>
    
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('lang.name') }}</th>
                    <th>{{ __('lang.description') }}</th>
                    <th>{{ __('lang.project') }}</th>
                    <th>{{ __('lang.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sections as $section)
                    <tr>
                        <td>{{ $section->name }}</td>
                        <td>{{ $section->description }}</td>
                        <td>{{ $section->project->name }}</td>
                        <td>
                            <a href="{{ route('sections.edit', $section) }}" class="btn btn-warning">{{ __('lang.edit') }}</a>
                            <form action="{{ route('sections.destroy', $section) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">{{ __('lang.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
