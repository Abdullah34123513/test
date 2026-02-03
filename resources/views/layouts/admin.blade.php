<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suma Admin | @yield('title', 'Dashboard')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4f46e5', // Indigo 600
                        secondary: '#1e293b', // Slate 800
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-secondary text-white flex flex-col shadow-lg">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-slate-700">
            <i data-lucide="shield" class="w-8 h-8 text-primary"></i>
            <span class="ml-3 text-xl font-bold tracking-tight">Suma Admin</span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-1">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-slate-700 hover:text-white rounded-lg transition-colors group {{ request()->routeIs('admin.dashboard') ? 'bg-primary text-white' : '' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-slate-700 hover:text-white rounded-lg transition-colors group {{ request()->routeIs('admin.users.*') ? 'bg-primary text-white' : '' }}">
                <i data-lucide="smartphone" class="w-5 h-5 mr-3"></i>
                <span class="font-medium">Devices</span>
            </a>

            <div class="pt-4 mt-4 border-t border-slate-700">
                <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-slate-700 hover:text-white rounded-lg transition-colors group">
                    <i data-lucide="settings" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium">Settings</span>
                </a>
            </div>
        </nav>

        <!-- User Profile (Bottom) -->
        <div class="p-4 border-t border-slate-700">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-slate-600 flex items-center justify-center font-bold">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="text-xs text-slate-400">Administrator</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Header -->
        <header class="h-16 bg-white border-b flex items-center justify-between px-8 shadow-sm z-10">
            <h1 class="text-xl font-semibold text-gray-800">@yield('header')</h1>
            
            <div class="flex items-center space-x-4">
                <button class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 relative">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-8 bg-gray-50">
            @yield('content')
        </main>
    </div>

    <!-- Init Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
