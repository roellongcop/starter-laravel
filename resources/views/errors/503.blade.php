<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance — {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0;
               display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0; }
        .card { text-align: center; max-width: 28rem; padding: 2rem; }
        h1 { font-size: 1.5rem; margin-bottom: .5rem; }
        p { color: #94a3b8; line-height: 1.6; }
        a { color: #93c5fd; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Maintenance in progress</h1>
        <p>{{ $exception?->getMessage() ?: 'A database restore is running. The app will be back in a moment.' }}</p>
        <p><a href="{{ route('login') }}">Operator sign-in</a></p>
    </div>
</body>
</html>
