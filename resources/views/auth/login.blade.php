<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-[#FDFDFC] flex items-center justify-center min-h-screen p-6">
    <div class="w-full max-w-md bg-white rounded-xl shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06),inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] p-8">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-[#1b1b18]">Sign in to Portal</h1>
            <p class="text-[#706f6c] mt-2">Enter your credentials to access the admin area.</p>
        </div>

        <form action="{{ route('login') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-[#1b1b18] mb-2">Email address</label>
                <input type="email" name="email" id="email" required 
                    class="w-full px-4 py-2.5 bg-[#FDFDFC] border border-[#19140035] rounded-lg focus:ring-2 focus:ring-[#f53003] focus:border-transparent outline-none transition-all"
                    placeholder="admin@admin.com">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-[#1b1b18] mb-2">Password</label>
                <input type="password" name="password" id="password" required 
                    class="w-full px-4 py-2.5 bg-[#FDFDFC] border border-[#19140035] rounded-lg focus:ring-2 focus:ring-[#f53003] focus:border-transparent outline-none transition-all"
                    placeholder="••••••••">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="h-4 w-4 rounded border-[#19140035] text-[#f53003] focus:ring-[#f53003]">
                    <label for="remember" class="ml-2 block text-sm text-[#706f6c]">Remember me</label>
                </div>
            </div>

            <button type="submit" 
                class="w-full py-3 px-4 bg-[#1b1b18] hover:bg-black text-white font-medium rounded-lg transition-colors duration-200">
                Sign in
            </button>
        </form>
    </div>
</body>
</html>
