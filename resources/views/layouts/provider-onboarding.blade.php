<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Provider') - {{ config('app.name', 'Grab A Hand') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        yellow: {
                            300: '#ffe07a',
                            400: '#ffd447',
                            500: '#f6b100',
                            600: '#d99b00',
                            700: '#b27f00',
                        },
                        amber: {
                            300: '#ffe07a',
                            400: '#ffd447',
                            500: '#f6b100',
                            600: '#d99b00',
                            700: '#b27f00',
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    @stack('head')
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between antialiased">

    <!-- Header Navigation -->
    <header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-400 to-yellow-500 flex items-center justify-center font-bold text-slate-950 shadow-lg shadow-amber-500/30">
                    GH
                </div>
                <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
                    Grab A Hand
                </span>
            </a>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition">Sign out</button>
                </form>
            @else
                <a href="{{ route('landing') }}" class="text-xs font-semibold px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition">
                    &larr; Back to Sign In
                </a>
            @endauth
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-1 w-full">
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-950/50 py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Grab A Hand Engine. All rights reserved.
    </footer>

    @stack('scripts')
</body>
</html>
