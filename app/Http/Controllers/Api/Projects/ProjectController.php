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

        // Create two sections "Livingroom" and "Bedroom" under the created project
        $sections = [
            ['project_id' => $project->id, 'name' => 'Livingroom', 'description' => 'Livingroom section'],
            ['project_id' => $project->id, 'name' => 'Bedroom', 'description' => 'Bedroom section'],
        ];
        $createdSections = Section::create([$sections]);

        return response()->json([
            'status' => true,
            'message' => 'Project created successfully',
            'data' => [
                'project' => $project,
                'sections' => $createdSections,
            ],
        ], 201);
    }    

    public function editProjectName(Request $request, Project $project)
    {
        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Check if the authenticated user is the project owner
        if ($project->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to edit this project',
            ], 403);
        }

        // Update the project name
        $project->name = $request->name;
        $project->save();

        return response()->json([
            'status' => true,
            'message' => 'Project name updated successfully',
            'data' => $project,
        ], 200);
    }

    public function deleteProject(Project $project)
    {
        if (auth()->check()) {
            if (auth()->user()->id == $project->user_id) {
                // Loop through each section and its devices
                foreach ($project->sections as $section) {
                    foreach ($section->devices as $device) {
                        $device->update([
                            'user_id' => null,
                            'section_id' => null,
                            'serial' => null,
                            'last_updated' => null,
                            'activation' => 0 ,
                        ]);
                    }
                }

                // you may want to delete the project or mark it inactive here if needed
                $project->delete(); // or any other inactivation logic
                return response()->json([
                    'status' => true,
                    'message' => 'Project has been deleted successfully',
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to delete this project',
            ], 403);
        }

        return response()->json([
            'status' => false,
            'message' => 'You must be logged in to delete a project',
        ], 401);
    }

    public function getProjectAccessDetails(Project $project)
    {
        // Ensure the authenticated user has access to view this project
        if (auth()->id() !== $project->user_id && !Member::where('project_id', $project->id)->where('member_id', auth()->id())->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to view this project',
            ], 403);
        }

        // Get project owner
        $owner = $project->user()->select('id', 'email', 'created_at')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'access'=>"owner",
            ];
        });

        // Get all members with access to the project and their permissions
        $members = $project->members()->with('user:id,created_at,email')->get()->map(function ($member) {
            return [
                'id' => $member->user->id,
                'email' => $member->user->email,
                'created_at' => $member->created_at,
                'access'=>"member",
            ];
        });
        // Merge owner and members into a single collection
        $users = $owner->merge($members);
        // Optionally reset keys
        $users = $users->values();

        return response()->json([
            'status' => true,
            'message' => 'Project access details retrieved successfully',
            'data' => [
                'project_id' => $project->id,
                'users' => $users,
            ],
        ], 200);
    }

    protected function logAction($action, $model, $modelId, $beforeData = null, $afterData = null)
    {
        Log::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model' => get_class($model),
            'model_id' => $modelId,
            'before_data' => $beforeData,
            'after_data' => $afterData,
        ]);
    }
}
