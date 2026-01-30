<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file', // Accepts any file type (image, video, etc)
        ]);

        if ($request->hasFile('file')) {
            // Store file in storage/app/public/uploads
            $path = $request->file('file')->store('uploads', 'public');
            $file = $request->file('file');

            // Save to DB
            $media = \App\Models\Media::create([
                'user_id' => $request->user()->id,
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(), // or deduce image/video
                'file_mime' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return response()->json([
                'message' => 'File uploaded successfully',
                'path' => $path,
                'url' => asset('storage/' . $path),
                'media_id' => $media->id,
            ]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }
}
