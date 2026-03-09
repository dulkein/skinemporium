<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-white text-[#1b1b18]">
        <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-[#FDFDFC] p-6 lg:p-8">
            <div class="relative w-full max-w-2xl">
                <main class="rounded-2xl bg-white p-10 shadow-sm ring-1 ring-black/5">
                    <h1 class="mb-4 text-3xl font-semibold tracking-tight">Laravel</h1>
                    <p class="text-base text-[#706f6c]">
                        Your application is ready. Start building by editing routes, controllers, and views.
                    </p>
                </main>
            </div>
        </div>
    </body>
</html>
