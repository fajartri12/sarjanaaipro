<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e40af">
    <title>@yield('title', 'Masuk') · Sarjana AI</title>
    @include('partials.fonts')
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-shell min-h-screen bg-gradient-to-br from-blue-50 via-white to-blue-100 text-gray-900 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="absolute right-4 top-4">
            @include('partials.theme-toggle', ['simple' => true])
        </div>

        <div class="w-full max-w-md">
            @include('partials.flash')
            @yield('content')
        </div>
    </div>
</body>
</html>
