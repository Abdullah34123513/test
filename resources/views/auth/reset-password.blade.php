<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #333; margin-bottom: 8px; font-size: 24px; }
        p { color: #666; margin-bottom: 24px; font-size: 14px; }
        label { display: block; color: #333; font-weight: 500; margin-bottom: 6px; font-size: 14px; }
        input { width: 100%; padding: 12px 16px; border: 2px solid #e1e1e1; border-radius: 8px; font-size: 16px; margin-bottom: 16px; transition: border-color 0.2s; }
        input:focus { outline: none; border-color: #667eea; }
        button { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .error { background: #fee; border: 1px solid #fcc; color: #c00; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .success { background: #efe; border: 1px solid #cfc; color: #060; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Reset Password</h1>
        <p>Enter your new password below.</p>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div class="success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ $email ?? old('email') }}" required autofocus>

            <label for="password">New Password</label>
            <input type="password" id="password" name="password" required>

            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>

            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>
