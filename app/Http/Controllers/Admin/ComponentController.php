<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    /**
     * Display a listing of the components.
     */
    public function index()
    {
        $components = Component::with('device')->get(); // Get all components with their associated devices
        return view('admin.components.index', compact('components'));
    }

    /**
     * Show the form for creating a new component.
     */
    public function create()
    {
        $devices = Device::all(); // Get all devices to assign a component
        return view('admin.components.create', compact('devices'));
    }

    /**
     * Store a newly created component in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'device_id' => 'required|exists:devices,id',
        ]);

        Component::create($request->all());

        return redirect()->route('admin.components.index')->with('success', 'Component created successfully.');
    }

    /**
     * Show the form for editing the specified component.
     */
    public function edit(Component $component)
    {
        $devices = Device::all(); // Get all devices to assign a component
        return view('admin.components.edit', compact('component', 'devices'));
    }

    /**
     * Update the specified component in storage.
     */
    public function update(Request $request, Component $component)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'device_id' => 'required|exists:devices,id',
        ]);

        $component->update($request->all());

        return redirect()->route('admin.components.index')->with('success', 'Component updated successfully.');
    }

    /**
     * Remove the specified component from storage.
     */
    public function destroy(Component $component)
    {
        $component->delete();

        return redirect()->route('admin.components.index')->with('success', 'Component deleted successfully.');
    }
}