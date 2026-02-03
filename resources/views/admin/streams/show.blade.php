@extends('layouts.admin')

@section('header', 'Live Audio Stream')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Streaming: {{ $stream->user->name }}</h2>
                    <p class="text-sm text-gray-500">Started {{ $stream->started_at->diffForHumans() }}</p>
                </div>
                <div id="status-badge" class="px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-700 flex items-center">
                    <span class="animate-pulse w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    {{ ucfirst($stream->status) }}
                </div>
            </div>

            <!-- Player Area -->
            <div class="p-12 text-center bg-gray-50">
                <div class="relative w-32 h-32 mx-auto mb-8 flex items-center justify-center">
                    <div class="absolute inset-0 bg-indigo-500 opacity-10 rounded-full animate-ping"></div>
                    <div class="relative bg-white p-6 rounded-full shadow-lg text-indigo-600">
                        <i data-lucide="mic" class="w-12 h-12"></i>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Listening to Device Audio...</h3>
                    <p class="text-gray-500 max-w-md mx-auto">
                        Audio chunks are being received and processed. 
                        The player below will auto-play available segments.
                    </p>
                    
                    <!-- Fallback / Simple Player -->
                    <div class="mt-8 p-4 bg-white rounded-lg border border-gray-200">
                        <audio controls autoplay class="w-full">
                            <!-- We would dynamically insert sources here -->
                            Your browser does not support audio.
                        </audio>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-100 bg-gray-50 flex justify-between">
                <a href="{{ route('admin.users.show', $stream->user_id) }}" class="text-gray-600 hover:text-gray-900 font-medium">
                    &larr; Back to Device
                </a>
                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium shadow-sm">
                    Stop Stream
                </button>
            </div>
        </div>
    </div>
@endsection
