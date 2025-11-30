<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? '東大ラーメンログ' }}</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

    <div class="flex flex-col h-screen max-w-md mx-auto bg-white shadow-2xl overflow-hidden relative">
        
        @include('partials.header')

        <main class="flex-1 overflow-y-auto p-4 scrollbar-hide">
            {{ $slot }}
        </main>

        @include('partials.footer')

    </div>

    

</body>
</html>