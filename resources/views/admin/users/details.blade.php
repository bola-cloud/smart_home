@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>@lang('lang.user_details', ['name' => $user->name])</h1>

        @foreach($projects as $project)
            <!-- Project Toggler -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <button class="btn btn-link text-white" data-bs-toggle="collapse" data-bs-target="#project-{{ $project->id }}">
                            {{ $project->name }}
                        </button>
                    </h5>
                    <div>
                        <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('projects.destroy', $project->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div id="project-{{ $project->id }}" class="collapse">
                    <div class="card-body">
                        <p>{{ $project->description }}</p>

                        <!-- Sections -->
                        @foreach($project->sections as $section)
                            <div class="card mb-2">
                                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link text-white" data-bs-toggle="collapse" data-bs-target="#section-{{ $section->id }}">
                                            {{ $section->name }}
                                        </button>
                                    </h6>
                                    <div>
                                        <a href="{{ route('sections.edit', $section->id) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('sections.destroy', $section->id) }}" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div id="section-{{ $section->id }}" class="collapse">
                                    <div class="card-body">
                                        <!-- Connection Devices -->
                                        <h6>@lang('lang.connection_devices')</h6>
                                        @foreach($section->devices as $device)
                                            <div class="card mb-2">
                                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                    <strong>{{ $device->name }}</strong>
                                                    <div>
                                                        <a href="{{ route('devices.edit', $device) }}" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('devices.destroy', $device) }}" method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <!-- Home Devices -->
                                                    <h6>@lang('lang.home_devices')</h6>
                                                    <ul>
                                                        @foreach($device->components as $component)
                                                            <li class="d-flex justify-content-between align-items-center">
                                                                <span>{{ $component->name }} ({{ $component->type }})</span>
                                                                <div>
                                                                    <a href="{{ route('components.edit', $component->id) }}" class="btn btn-warning btn-sm">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <form action="{{ route('components.destroy', $component->id) }}" method="POST" style="display:inline-block;">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('css')
<style>
    /* Limit the height of collapsible content and enable scrolling */
    .card-body {
        max-height: 400px; /* Adjust as per your layout */
        overflow-y: auto; /* Enable vertical scrolling */
    }

    /* Prevent horizontal expansion */
    .container-fluid {
        max-width: 100%;
        overflow-x: hidden; /* Prevent horizontal scrollbars */
    }
</style>
@endpush

@push('js')
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- FontAwesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
@endpush
