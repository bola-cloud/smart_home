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

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully',
            'data' => $project,
        ], 201);
    }    
}
