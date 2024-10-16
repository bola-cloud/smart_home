@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card p-3">
        <div class="card-header d-flex justify-content-between">
            <h1>{{ __('lang.components_for_device') }}: {{ $device->name }}</h1>
        </div>
        <div class="card-body">
            <form id="sortable-components-form" action="{{ route('components.update_order_and_edit', $device->id) }}" method="POST">
                @csrf
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('lang.component_name') }}</th>
                            <th>{{ __('lang.order') }}</th>
                            {{-- <th>{{ __('lang.actions') }}</th> --}}
                        </tr>
                    </thead>
                    <tbody id="sortable-components">
                        @foreach($device->components->sortBy('order') as $component)
                            <tr data-id="{{ $component->id }}">
                                <!-- Editable Component Name Field -->
                                <td>
                                    <input type="text" name="components[{{ $component->id }}][name]" class="form-control" value="{{ $component->name }}">
                                </td>
                            
                                <!-- Display Component Order (Auto-updated by Sortable.js) -->
                                <td class="component-order">{{ $component->order }}</td>
                                
                                <!-- Edit and Delete Actions -->
                                {{-- <td>
                                    <form action="{{ route('components.destroy', $component->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">{{ __('lang.delete') }}</button>
                                    </form>
                                </td> --}}
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(!$device->components->isEmpty())
                <button type="submit" class="btn btn-primary">{{ __('lang.edit') }}</button>
                @else 
                    <span class="text-center">{{__('lang.no_components')}}</span>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<!-- Include Sortable JS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.13.0/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Sortable for the components table
        const sortable = new Sortable(document.getElementById('sortable-components'), {
            animation: 150,
            onEnd: function(evt) {
                updateComponentOrder();
            }
        });

        // Function to update the order of components when dragged and dropped
        function updateComponentOrder() {
            const rows = document.querySelectorAll('#sortable-components tr');
            rows.forEach((row, index) => {
                row.querySelector('.component-order').textContent = index + 1;
            });
        }

        // When submitting the form, collect the new order and edited data
        document.getElementById('sortable-components-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const orderData = [];

            document.querySelectorAll('#sortable-components tr').forEach((row, index) => {
                const componentId = row.getAttribute('data-id');
                orderData.push({
                    id: componentId,
                    order: index + 1,
                    name: row.querySelector(`input[name="components[${componentId}][name]"]`).value,
                });
            });

            // Send the updated order and component data via AJAX
            fetch('{{ route('components.update_order_and_edit', $device->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ components: orderData })
            })

            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('{{ __("lang.order_updated_successfully") }}');
                    location.reload();
                } else {
                    alert('{{ __("lang.error_updating_order") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
</script>
@endpush
