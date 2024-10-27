<?php

namespace App\Http\Controllers\Api\Sections;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    // Create a new section for a specific project
    public function store(Request $request, Project $project)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Ensure that the authenticated user owns the project
        if ($project->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to add sections to this project',
            ], 403);
        }

        // Create a new section associated with the specified project
        $section = Section::create([
            'project_id' => $project->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Section created successfully',
            'data' => $section,
        ], 201);
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
}