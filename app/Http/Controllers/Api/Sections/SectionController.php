<?php

namespace App\Http\Controllers\Api\Sections;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Project;
use App\Models\Section;
use App\Models\Device;
use App\Models\Member;

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
        // Validate the request
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|numeric|exists:projects,id',
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
    
        // Check if the authenticated user is a User or a Member
        $auth = Auth::user();
        $isOwner = $project->user_id === $auth->id;
    
        if ($isOwner) {
            // For owners, retrieve all sections in the project
            $sections = $project->sections;
    
            return response()->json([
                'status' => true,
                'message' => 'Sections retrieved successfully for owner',
                'data' => $sections,
            ], 200);
        } else {
            // For members, get the sections with devices they have access to in this project
    
            // Find member entry for the authenticated user and project
            $member = Member::where('member_id', $auth->id)->where('project_id', $project->id)->first();
    
            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to access this project',
                ], 403);
            }
    
            // Extract device IDs from the member's devices JSON column
            $memberDevices = $member->devices;
    
            if (!$memberDevices || !is_array($memberDevices)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No devices found for the member',
                    'data' => null,
                ], 404);
            }
    
            // Get only sections containing devices the member has access to
            $deviceIds = array_keys($memberDevices);
    
            // Find sections in the project where the member's devices are located
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
    }
     
    public function editSectionName(Request $request, Section $section)
    {
        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Check if the authenticated user is the owner of the project this section belongs to
        if ($section->project->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to edit this section',
            ], 403);
        }

        // Update the section name
        $section->name = $request->name;
        $section->save();

        return response()->json([
            'status' => true,
            'message' => 'Section name updated successfully',
            'data' => $section,
        ], 200);
    }

}