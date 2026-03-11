<?php

namespace App\Http\Controllers;

use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    private function getFolderPath(string $type): string
    {
        return match (strtoupper($type)) {
            'DESTINATION_IMAGE' => 'destinations_upload',
            'USER_IMAGE' => 'users_upload',
            'TOUR_IMAGE' => 'tours_upload',
            default => 'misc',
        };
    }

    // 1. Upload Single File to Cloudinary
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|image', // Validate file ảnh
            'type' => 'required|string',
        ]);

        $folder = $this->getFolderPath($request->input('type'));
        
        // Gọi service upload lên Cloudinary
        $publicId = $this->cloudinaryService->uploadSingleFile(
            $request->file('file'),
            $folder
        );

        return $this->success( [
            'filePath' => $publicId
        ], 'File uploaded successfully');
    }

    // 2. Upload Multiple Files to Cloudinary
    public function uploadMultipleFiles(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|image',
            'type' => 'required|string',
        ]);

        $folder = $this->getFolderPath($request->input('type'));

        $publicIds = $this->cloudinaryService->uploadMultipleFiles(
            $request->file('files'),
            $folder
        );

        $fileInfos = collect($publicIds)->map(function ($id) {
            return ['url' => $id];
        });

        return response()->json([
            'message' => 'Files uploaded successfully',
            'totalFiles' => count($publicIds),
            'files' => $fileInfos
        ]);
    }
}