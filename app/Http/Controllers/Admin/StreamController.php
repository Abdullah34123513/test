<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    public function show(LiveStream $stream)
    {
        return view('admin.streams.show', compact('stream'));
    }

    public function status(LiveStream $stream)
    {
        return response()->json([
            'status' => $stream->status,
            // Assuming there's a relationship or way to get latest chunk
            // For now just returning status to control the UI
        ]);
    }
}
