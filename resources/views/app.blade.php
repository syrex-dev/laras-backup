<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="google" content="notranslate">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $institutionName = \Illuminate\Support\Facades\Schema::hasTable('institution_profiles')
                ? (\App\Domain\Instansi\Models\InstitutionProfile::current()?->name ?? config('app.name', 'E-Office'))
                : config('app.name', 'E-Office');
        @endphp
        <title inertia>{{ $institutionName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="h-full bg-slate-50 text-slate-800 antialiased font-sans">
        @inertia
    </body>
</html>
