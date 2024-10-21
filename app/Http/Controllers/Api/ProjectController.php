<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Project;
use App\Models\Section;
use App\Models\Device;
use App\Models\Member;

class ProjectController extends Controller
{
    public function userProjects()
    {
        // Check if the user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'You are not logged in'], 401);
        }
    
        // Get the currently authenticated user or member
        $auth = Auth::user();
        $authType = $auth instanceof Member ? 'member' : 'user';
    
        if ($authType === 'user') {
            // If authenticated as a user, retrieve all projects associated with the user
            $projects = $auth->projects;
    
            return response()->json([
                'status' => true,
                'message' => 'User projects retrieved successfully',
                'data' => $projects,
            ], 200);
        } elseif ($authType === 'member') {
            // If authenticated as a member, retrieve projects based on devices
    
            // Assuming 'devices' is stored as an array or JSON in the members table
            $deviceIds = $auth->devices;
    
            // Find all sections where these devices are located
            $sections = Section::whereHas('devices', function ($query) use ($deviceIds) {
                $query->whereIn('id', $deviceIds);
            })->get();
    
            // Retrieve the unique projects associated with these sections
            $projects = Project::whereIn('id', $sections->pluck('project_id'))->distinct()->get();
    
            return response()->json([
                'status' => true,
                'message' => 'Member projects retrieved successfully',
                'data' => $projects,
            ], 200);
        }
    
        return response()->json(['message' => 'Unknown authentication type'], 400);
    }

    public function getProjectSections(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|numeric',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Find the project by its ID
        $project = Project::find($request->project_id);
    
        // Check if the project exists
        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found',
            ], 404);
        }
    
        // Check if the authenticated user is a User or Member
        $auth = Auth::user();
        $authType = $auth instanceof Member ? 'member' : 'user';
    
        if ($authType === 'user') {
            // For users, ensure they own the project
            if ($project->user_id !== $auth->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to access this project',
                ], 403);
            }
    
            // Get all sections of the project
            $sections = $project->sections;
    
            return response()->json([
                'status' => true,
                'message' => 'Sections retrieved successfully',
                'data' => $sections,
            ], 200);
        } elseif ($authType === 'member') {
            // For members, get the sections related to the devices they have access to in this project
    
            // Get device IDs from the member's devices column
            $deviceIds = $auth->devices;
    
            // Find sections in the given project where the member's devices are located
            $sections = Section::where('project_id', $project->id)
                ->whereHas('devices', function ($query) use ($deviceIds) {
                    $query->whereIn('id', $deviceIds);
                })
                ->get();
    
            return response()->json([
                'status' => true,
                'message' => 'Sections retrieved successfully for the member',
                'data' => $sections,
            ], 200);
        }
    
        return response()->json([
            'status' => false,
            'message' => 'Unknown authentication type',
        ], 400);
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
