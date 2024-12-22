@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h1>@lang('lang.user_details', ['name' => $user->name])</h1>

        @foreach($projects as $project)
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5>{{ $project->name }}</h5>
                </div>
                <div class="card-body">
                    <p>{{ $project->description }}</p>

                    <!-- Sections -->
                    <h6>@lang('lang.sections')</h6>
                    @foreach($project->sections as $section)
                        <div class="card mb-2">
                            <div class="card-header bg-secondary text-white">
                                <strong>{{ $section->name }}</strong>
                            </div>
                            <div class="card-body">
                                <p>{{ $section->description }}</p>

                                <!-- Devices -->
                                <h6>@lang('lang.devices')</h6>
                                @foreach($section->devices as $device)
                                    <div class="card mb-2">
                                        <div class="card-header bg-light">
                                            <strong>{{ $device->name }}</strong> (Serial: {{ $device->serial }})
                                        </div>
                                        <div class="card-body">

                                            <!-- Components -->
                                            <h6>@lang('lang.components')</h6>
                                            <ul>
                                                @foreach($device->components as $component)
                                                    <li>{{ $component->name }} ({{ $component->type }})</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
