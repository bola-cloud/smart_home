<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        // Retrieve all blogs from the database
        $blogs = Blog::all();

        // Map through each blog to customize the image URL
        $blogs = $blogs->map(function ($blog) {
            // Generate full image URL using asset() helper
            $blog->image = asset('storage/' . $blog->image);
            return $blog;
        });

        // Return the blogs with the full image URL as JSON response
        return response()->json([
            'status' => true,
            'data' => $blogs
        ]);
    }
}
