<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'nullable|string|max:20',
            'category' => 'required|in:admin,user,technical',
            'password' => 'required|string|min:8|confirmed',
        ]);
    
        User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'category' => $validatedData['category'],
            'password' => bcrypt($validatedData['password']),
        ]);
    
        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }    

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'category' => 'required|in:admin,user,technical',
        ]);
    
        $user->update($validatedData);
    
        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }
    
    public function showDetails(User $user)
    {
        // Fetch user's projects with sections and components
        $projects = $user->projects()->with('sections.devices.components')->get();

        return view('admin.users.details', compact('user', 'projects'));
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
