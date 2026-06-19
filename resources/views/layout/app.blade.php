<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FinanceOS') — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    @stack('styles')
</head>
<body class="{{ session('sidebar_collapsed', false) ? 'sidebar-collapsed' : '' }}">

    {{-- SIDEBAR --}}
    @include('layout.sidebar')

    {{-- MAIN WRAPPER --}}
    <div class="main-wrapper" id="mainWrapper">

        {{-- TOPBAR --}}
        @include('layout.topbar')

        {{-- PAGE CONTENT --}}
        <main class="page-content">
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-xmark"></i>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>

    </div>

    {{-- SIDEBAR OVERLAY (mobile) --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="{{ asset('js/layout.js') }}"></script>
    @stack('scripts')
</body>
</html>
