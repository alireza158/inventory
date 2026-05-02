<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'سیستم انبار آریا جانبی') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="text-gray-900 antialiased" style="font-family: 'Vazirmatn', sans-serif;">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-8 sm:pt-0 bg-gradient-to-br from-indigo-100 via-slate-100 to-violet-100">
           <div class="flex flex-col items-center justify-center text-center">
    <a href="/" class="flex justify-center">
        <x-application-logo class="w-20 h-20 sm:w-16 sm:h-16 object-contain drop-shadow-md" />
    </a>

    <p class="mt-2 text-xs sm:text-sm text-slate-500">
        {{ config('app.name', 'سیستم انبار آریا جانبی') }}
    </p>
</div>
            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white/95 shadow-xl shadow-slate-200/80 overflow-hidden rounded-2xl border border-white/80 backdrop-blur">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
