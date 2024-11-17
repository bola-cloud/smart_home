<?php

namespace App\Http\Controllers\Api\IrCode;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Component;

class IrCodeController extends Controller
{
    private $basePath;

    public function __construct()
    {
        // Define base directory for IR files
        $this->basePath = storage_path('app/irdata');
    }

    // Get all device types (e.g., TVs, ACs, etc.)
    public function getDeviceTypes()
    {
        $folders = File::directories($this->basePath);
        $deviceTypes = array_map('basename', $folders);
        return response()->json($deviceTypes);
    }

    // Get all brands within a device type (e.g., all brands for "ACs")
    public function getBrands($deviceType)
    {
        $devicePath = $this->basePath . '/' . $deviceType;
        if (!File::exists($devicePath)) {
            return response()->json(['error' => 'Device type not found'], 404);
        }

        $brands = File::directories($devicePath);
        $brandNames = array_map('basename', $brands);
        return response()->json($brandNames);
    }

    // Get all files for a specific brand under a device type
    public function getFiles($deviceType, $brand)
    {
        $brandPath = $this->basePath . '/' . $deviceType . '/' . $brand;
        if (!File::exists($brandPath)) {
            return response()->json(['error' => 'Brand not found'], 404);
        }

        $files = File::files($brandPath);
        $fileNames = array_map(function ($file) {
            return $file->getFilename();
        }, $files);

        return response()->json($fileNames);
    }

    // Get contents of a specific IR file
    public function getFileContent($deviceType, $brand, $filename)
    {
        $filePath = $this->basePath . '/' . $deviceType . '/' . $brand . '/' . $filename;

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $fileContent = File::get($filePath);
        $buttons = $this->parseIRFile($fileContent);

        return response()->json(['filename' => $filename, 'buttons' => $buttons]);
    }

    /**
     * Parses the contents of an IR file into structured JSON data.
     *
     * @param string $fileContent The raw content of the .ir file.
     * @return array An array of parsed button data.
     */
    private function parseIRFile($fileContent)
    {
        $buttons = [];
        $blocks = explode("#", $fileContent);

        foreach ($blocks as $block) {
            $lines = array_filter(array_map('trim', explode("\n", trim($block))));
            $buttonData = [];

            foreach ($lines as $line) {
                if (strpos($line, ': ') !== false) {
                    list($key, $value) = explode(': ', $line, 2);
                    $buttonData[strtolower($key)] = trim($value);
                }
            }

            if (!empty($buttonData)) {
                $buttons[] = $buttonData;
            }
        }

        return $buttons;
    }

    // Get contents of all IR files within a specific brand under a device type
    public function getAllFilesContent($deviceType, $brand)
    {
        $brandPath = $this->basePath . '/' . $deviceType . '/' . $brand;

        // Check if the brand directory exists
        if (!File::exists($brandPath)) {
            return response()->json(['error' => 'Brand not found'], 404);
        }

        // Initialize an array to hold the files and their content
        $allFilesContent = [];

        // Loop through each file in the brand directory
        $files = File::files($brandPath);
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $fileContent = File::get($file->getPathname());

            // Parse the file content to get button data
            $buttons = $this->parseIRFile($fileContent);

            // Add the file's content to the result array
            $allFilesContent[] = [
                'filename' => $filename,
                'buttons' => $buttons
            ];
        }

        // Return all files with their parsed content as JSON
        return response()->json($allFilesContent);
    }

    public function attachFilePaths(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'component_id' => 'required|exists:components,id', // Fixed typo: changed 'exist' to 'exists'
            'file_path' => 'required|string',
        ]);
        
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'You are not logged in'], 401);
        }
        
        $component = Component::find($validated['component_id']);

        if (!$component) {
            return response()->json(['message' => 'This device does not exist'], 401);
        }

        if ($component->device->user_id == Auth::user()->id) {
            return response()->json(['error' => 'This device does not belongs to you'], 404);
        }

        $filePath = $validated['file_path'];
        $component->update([
            'file_path' => $filePath
        ]);

        return response()->json([
            'status' => true,
            'message' => 'File paths attached successfully.',
        ], 200);
    }

}
