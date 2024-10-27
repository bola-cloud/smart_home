<?php

namespace App\Http\Controllers\Api\Projects;

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
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'You are not logged in'], 401);
        }
    
        // Get the authenticated user
        $user = Auth::user();
    
        // Retrieve projects owned by the user and add 'type' as 'owner'
        $ownedProjects = $user->projects->map(function ($project) {
            $project->type = 'owner';
            return $project;
        });
    
        // Retrieve projects where the user is a member and add 'type' as 'member'
        $memberProjectIds = Member::where('member_id', $user->id)->pluck('project_id')->unique();
        $projectsAsMember = Project::whereIn('id', $memberProjectIds)->get()->map(function ($project) {
            $project->type = 'member';
            return $project;
        });
    
        // Combine both collections and remove duplicates, if any
        $allAccessibleProjects = $ownedProjects->merge($projectsAsMember)->unique('id')->values();
    
        return response()->json([
            'status' => true,
            'message' => 'Accessible projects retrieved successfully',
            'data' => $allAccessibleProjects,
        ], 200);
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
            $memberDevices = $auth->devices;
    
            if (!$memberDevices || !is_array($memberDevices)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No devices found for the member',
                    'data' => null,
                ], 404);
            }
    
            // Extract device IDs
            $deviceIds = array_keys($memberDevices);
    
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
