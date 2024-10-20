<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function userProjects()
    {
        // Get the currently authenticated user
        $user = Auth::user();

        // Retrieve the projects for the logged-in user
        $projects = $user->projects;

        return response()->json([
            'status' => true,
            'message' => 'User projects retrieved successfully',
            'data' => $projects,
        ]);
    }
}
