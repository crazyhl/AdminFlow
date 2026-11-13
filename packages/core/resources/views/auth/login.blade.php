<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminFlow 登录</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f3f4f6; }
        .card { background: white; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; }
        .title { font-size: 1.25rem; font-weight: bold; margin-bottom: 1.5rem; text-align: center; color: #111827; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: 0.875rem; margin-bottom: 0.5rem; color: #374151; }
        input[type="email"], input[type="password"] { width: 100%; box-sizing: border-box; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; }
        .error { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
        button { width: 100%; padding: 0.625rem; background: #2563eb; color: white; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; color: #4b5563; }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">AdminFlow 登录</div>
        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="form-group">
                <label for="email">管理员邮箱</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input id="password" type="password" name="password" required>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <div class="checkbox-group">
                <input id="remember" type="checkbox" name="remember">
                <label for="remember" style="margin-bottom: 0;">记住登录状态</label>
            </div>
            <button type="submit">登 录</button>
        </form>
    </div>
</body>
</html>
