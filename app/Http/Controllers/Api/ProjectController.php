<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function userProjects()
    {
        // Check if the user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'You are not logged in'], 401);
        }
    
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

    public function getProjectSections(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|numeric',
        ]);
        // Find the project by its ID
        $project = Project::find($request->project_id);

        // Check if the project exists
        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found',
            ], 404);
        }

        // Get the sections related to the project
        $sections = $project->sections;

        return response()->json([
            'status' => true,
            'message' => 'Sections retrieved successfully',
            'data' => $sections,
        ], 200);
    }

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Get the authenticated user
        $user = $request->user();

        // Create a new project associated with the authenticated user
        $project = Project::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully',
            'data' => $project,
        ], 201);
    }

    
    
}
