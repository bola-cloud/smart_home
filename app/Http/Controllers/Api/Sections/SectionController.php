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

    public function getAccessibleSections()
    {
        $auth = Auth::user();
    
        // Retrieve sections from projects the user owns
        $ownedSections = Section::whereHas('project', function ($query) use ($auth) {
            $query->where('user_id', $auth->id);
        })->get(['id', 'name', 'created_at', 'updated_at', 'project_id']);  // Select specific fields
    
        // Retrieve sections for projects where the user is a member
        $memberSections = Member::where('member_id', $auth->id)
            ->with('project.sections.devices')  // Load project sections and their devices
            ->get()
            ->flatMap(function ($member) {
                $deviceIds = array_keys($member->devices);
    
                // Filter sections to only include those with devices the member has access to
                return $member->project->sections->filter(function ($section) use ($deviceIds) {
                    return $section->devices->pluck('id')->intersect($deviceIds)->isNotEmpty();
                });
            });
    
        // Merge owned sections and member-accessible sections, ensuring no duplicates
        $sections = $ownedSections->merge($memberSections)->unique('id')->values();
    
        // Transform sections to include only the required fields
        $sectionsData = $sections->map(function ($section) {
            return [
                'section_id' => $section->id,
                'name' => $section->name,
                'created_at' => $section->created_at,
                'updated_at' => $section->updated_at,
                'project_id' => $section->project_id,
            ];
        });
    
        return response()->json([
            'status' => true,
            'message' => 'Accessible sections retrieved successfully',
            'data' => $sectionsData,
        ], 200);
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

    public function deleteSection(Section $section)
    {
        if (auth()->check()) {
            if (auth()->user()->id == $section->project->user_id) {
                // Loop through each device in the section and inactivate it
                foreach ($section->devices as $device) {
                    $device->update([
                        'user_id' => null,
                        'section_id' => null,
                        'serial' => null,
                        'last_updated' => null,
                        'activation' => 0 ,
                    ]);
                }

                // Delete or mark the section as inactive
                $section->delete(); // or any other inactivation logic
                return response()->json([
                    'status' => true,
                    'message' => 'Section has been delted successfully',
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to delete this section',
            ], 403);
        }

        return response()->json([
            'status' => false,
            'message' => 'You must be logged in to delete a section',
        ], 401);
    }

}