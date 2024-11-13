<?php

namespace App\Http\Controllers\Api\IrCode;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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
        return response()->json(['filename' => $filename, 'data' => $fileContent]);
    }
}
