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
        $directoryPath = $this->basePath . '/' . $deviceType . '/' . $brand;
    
        // Check if the directory exists
        if (!File::exists($directoryPath)) {
            return response()->json(['error' => 'Brand not found'], 404);
        }
    
        // Get all files in the directory
        $files = File::files($directoryPath);
    
        // Prepare the response with the user-added flag
        $fileList = array_map(function ($file) {
            return [
                'file_name' => $file->getFilename(),
                'is_user_added' => strpos($file->getFilename(), 'user_') === 0, // Check if file name starts with 'user_'
            ];
        }, $files);
    
        return response()->json($fileList);
    }    

    public function getFileContent($deviceType, $brand, $fileName)
    {
        $filePath = $this->basePath . '/' . $deviceType . '/' . $brand . '/' . $fileName;
    
        // Check if the file exists
        if (!File::exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }
    
        // Get the file content
        $fileContent = File::get($filePath);
    
        // Determine if the file is user-added
        $isUserAdded = strpos($fileName, 'user_') === 0;
    
        // Parse the file content into JSON
        $buttons = $this->parseIRFile($fileContent);
    
        // Return the file content with the flag and parsed data
        return response()->json([
            'file_name' => $fileName,
            'is_user_added' => $isUserAdded,
            'buttons' => $buttons,
        ]);
    }
    
    /**
     * Parse IR file content into structured JSON.
     *
     * @param string $fileContent
     * @return array
     */
    private function parseIRFile($fileContent)
    {
        $buttons = [];
        $blocks = explode("#", $fileContent); // Split content by sections
    
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
        $directoryPath = $this->basePath . '/' . $deviceType . '/' . $brand;
    
        // Check if the brand directory exists
        if (!File::exists($directoryPath)) {
            return response()->json(['error' => 'Brand not found'], 404);
        }
    
        $files = File::files($directoryPath);
        $allFilesContent = [];
    
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $fileContent = File::get($file->getPathname());
    
            // Add the file content and flag
            $isUserAdded = strpos($fileName, 'user_') === 0; // Check if the file is user-added
            
            // Parse the file content into structured data
            $buttons = $this->parseIRFile($fileContent); // Assuming parseIRFile() is the method that parses the content
            
            // Add the parsed data to the response
            $allFilesContent[] = [
                'file_name' => $fileName,
                'is_user_added' => $isUserAdded,
                'buttons' => $buttons, // Parsed content (instead of raw content)
            ];
        }
    
        // Return the content as a JSON response in the same format as getFileContent
        return response()->json([
            'data' => $allFilesContent,
        ]);
    }    

    public function attachFilePaths(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'component_id' => 'required|exists:components,id', // Fixed typo: changed 'exist' to 'exists'
            'file_path' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
        ]);
        
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'You are not logged in'], 401);
        }
        
        $component = Component::find($validated['component_id']);

        if (!$component) {
            return response()->json(['message' => 'This device does not exist'], 401);
        }

        if ($component->device->user_id != Auth::user()->id) {
            return response()->json(['error' => 'This device does not belongs to you'], 404);
        }

        $filePath = $validated['file_path'];
        $component->update([
            'file_path' => $filePath,
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'File paths attached successfully.',
        ], 200);
    }

    public function deattachFilePaths(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'component_id' => 'required|exists:components,id', 
        ]);
        
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'You are not logged in'], 401);
        }
        
        $component = Component::find($validated['component_id']);

        if (!$component) {
            return response()->json(['message' => 'This device does not exist'], 401);
        }

        if ($component->file_path == null) {
            return response()->json(['message' => 'This remote does not exist'], 401);
        }

        if ($component->device->user_id != Auth::user()->id) {
            return response()->json(['error' => 'This device does not belongs to you'], 404);
        }

        $component->update([
            'file_path' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'File paths attached successfully.',
        ], 200);
    }

    public function createDeviceFile(Request $request)
    {
        // Decode the JSON input
        $decodedData = json_decode($request->getContent(), true);
    
        // Validate the required fields
        $validator = \Validator::make($decodedData, [
            'device' => 'required|string', // Example: TVs, ACs
            'brand_name' => 'required|string', // Example: Amazon
            'name' => 'required|string', // Example: Component name
            'file_content' => 'nullable|string', // File content for new files
            'buttons' => 'nullable|array', // Buttons to add to the file
            'is_new_file' => 'required|boolean', // Flag for new file creation
            'component_id' => 'nullable|exists:components,id', // Optional: Component ID to attach file
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 400);
        }
    
        // Extract validated data
        $deviceType = $decodedData['device'];
        $brandName = $decodedData['brand_name'];
        $componentName = $decodedData['name']; // Component name
        $isNewFile = $decodedData['is_new_file'];
        $fileContent = $decodedData['file_content'] ?? "Filetype: IR signals file\nVersion: 1\n";
        $buttons = $decodedData['buttons'] ?? [];
        $componentId = $decodedData['component_id'];
    
        // Generate a file name based on the component name
        $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $componentName) . '.ir'; // Replace invalid characters with '_'
    
        // Construct the directory path
        $directoryPath = $this->basePath . '/' . $deviceType . '/' . $brandName;
    
        // Check if the device type directory exists
        if (!File::exists($this->basePath . '/' . $deviceType)) {
            return response()->json([
                'status' => false,
                'message' => 'Device type does not exist.',
            ], 404);
        }
    
        // Ensure the brand directory exists
        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true); // Create folders recursively
        }
    
        // Define the full file path
        $filePath = $directoryPath . '/' . $fileName;
    
        if ($isNewFile) {
            // Handle new file creation
            if (File::exists($filePath)) {
                return response()->json([
                    'status' => false,
                    'message' => 'File already exists. Use the `is_new_file` flag to add buttons instead.'
                ], 400);
            }
    
            // Append buttons/commands if provided
            foreach ($buttons as $button) {
                $fileContent .= $this->formatButtonData($button);
            }
    
            // Save the new file content
            File::put($filePath, $fileContent);
        } else {
            // Handle updating an existing file
            if (!File::exists($filePath)) {
                return response()->json([
                    'status' => false,
                    'message' => 'File does not exist. Use the `is_new_file` flag to create it.'
                ], 404);
            }
    
            // Read the existing content
            $existingContent = File::get($filePath);
    
            // Append buttons/commands
            foreach ($buttons as $button) {
                $existingContent .= $this->formatButtonData($button);
            }
    
            // Save the updated content
            File::put($filePath, $existingContent);
        }
    
        // Optional: Attach file to a component if component_id is provided
        if ($componentId) {
            $component = Component::find($componentId);
    
            if (!$component) {
                return response()->json(['message' => 'Component not found.'], 404);
            }
    
            if (!Auth::check() || $component->device->user_id != Auth::user()->id) {
                return response()->json(['error' => 'You do not have permission to attach this file.'], 403);
            }
    
            $component->update([
                'file_path' => $deviceType . '/' . $brandName . '/' . $fileName,
                'name' => $componentName,
                'manual' => true,
            ]);
        }
    
        return response()->json([
            'status' => true,
            'message' => $isNewFile ? 'File created successfully with commands.' : 'Buttons added successfully.',
            'file_path' => $deviceType . '/' . $brandName . '/' . $fileName,
        ], 200);
    }
    
    /**
     * Format button data into the file structure.
     *
     * @param array $button
     * @return string
     */
    private function formatButtonData($button)
    {
        $buttonBlock = "\n#\n";
        $buttonBlock .= "name: {$button['name']}\n";
        $buttonBlock .= "type: {$button['type']}\n";
    
        if ($button['type'] === 'raw') {
            $buttonBlock .= "frequency: {$button['frequency']}\n";
            $buttonBlock .= "duty_cycle: {$button['duty_cycle']}\n";
            $buttonBlock .= "data: {$button['data']}\n";
        } else {
            $buttonBlock .= "protocol: {$button['protocol']}\n";
            $buttonBlock .= "address: {$button['address']}\n";
            $buttonBlock .= "command: {$button['command']}\n";
        }
    
        return $buttonBlock;
    }

    public function overwriteDeviceFileButtons(Request $request)
    {
        // Decode the JSON input
        $decodedData = json_decode($request->getContent(), true);

        // Validate the required fields
        $validator = \Validator::make($decodedData, [
            'device' => 'required|string', // Example: TVs, ACs
            'brand_name' => 'required|string', // Example: Amazon
            'name' => 'required|string', // Example: Component name
            'buttons' => 'required|array', // Buttons to overwrite in the file
            'component_id' => 'nullable|exists:components,id', // Optional: Component ID to attach file
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        // Extract validated data
        $deviceType = $decodedData['device'];
        $brandName = $decodedData['brand_name'];
        $componentName = $decodedData['name']; // Component name
        $buttons = $decodedData['buttons'];
        $componentId = $decodedData['component_id'];

        // Generate a file name based on the component name
        $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $componentName) . '.ir'; // Replace invalid characters with '_'

        // Construct the directory path
        $directoryPath = $this->basePath . '/' . $deviceType . '/' . $brandName;
        
        // Define the full file path
        $filePath = $directoryPath . '/' . $fileName;

        // Check if the device type directory exists
        if (!File::exists($this->basePath . '/' . $deviceType)) {
            return response()->json([
                'status' => false,
                'message' => 'Device type does not exist.',
            ], 404);
        }

        // Ensure the brand directory exists
        if (!File::exists($directoryPath)) {
            return response()->json([
                'status' => false,
                'message' => 'Brand directory does not exist.',
            ], 404);
        }

        // Check if the file exists
        if (!File::exists($filePath)) {
            return response()->json([
                'status' => false,
                'message' => 'File does not exist. Cannot overwrite buttons.',
            ], 404);
        }

        // Format the buttons data into the file structure
        $newFileContent = "Filetype: IR signals file\nVersion: 1\n"; // Optional header

        // Iterate over the buttons and append them to the file content
        foreach ($buttons as $button) {
            $newFileContent .= $this->formatButtonData($button);
        }

        // Save the new content to the file (this will overwrite the file content)
        File::put($filePath, $newFileContent);

        // Optional: Attach file to a component if component_id is provided
        if ($componentId) {
            $component = Component::find($componentId);

            if (!$component) {
                return response()->json(['message' => 'Component not found.'], 404);
            }

            if (!Auth::check() || $component->device->user_id != Auth::user()->id) {
                return response()->json(['error' => 'You do not have permission to attach this file.'], 403);
            }

            // Update the component with the new file path and details
            $component->update([
                'file_path' => $deviceType . '/' . $brandName . '/' . $fileName,
                'name' => $componentName,
                'manual' => true,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Buttons data overwritten successfully.',
            'file_path' => $deviceType . '/' . $brandName . '/' . $fileName,
        ], 200);
    }

}
