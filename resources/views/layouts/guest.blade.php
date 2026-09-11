<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-navy-900">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            @yield('content')
        </div>
    </div>
</body>
</html>
