<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Users</h1>
    <p>Generated {{ now()->toDayDateTimeString() }} · {{ $users->count() }} rows</p>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Status</th><th>Roles</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->user_status->value }}</td>
                    <td>{{ $user->roles->pluck('name')->implode(', ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
