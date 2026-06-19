{{--
    Dynamic Sidebar Partial
    Usage: @include('layouts.partials.sidebar')
    
    Menu items are driven by config/sidebar.php
    Active state is auto-detected via Route::is()
--}}

@php
    $menus = config('sidebar.menus');
    $user = auth()->user();
@endphp

<aside class="sidebar" id="sidebar">

    {{-- Brand --}}
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name">FinanceOS</span>
            <span class="brand-tagline">Pro Suite</span>
        </div>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" title="Close sidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Search --}}
    <div class="sidebar-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="sidebarSearch" placeholder="Search menu…" autocomplete="off">
    </div>

    {{-- Navigation --}}
    <nav class="sidebar-nav" id="sidebarNav">
        @foreach ($menus as $group)
            {{-- Group heading --}}
            <div class="nav-group">
                <span class="nav-group-label">{{ $group['label'] }}</span>

                @foreach ($group['items'] as $item)
                    @php
                        $hasChildren = !empty($item['children']);
                        $isActive =
                            (isset($item['route']) && request()->routeIs($item['route'])) ||
                            ($hasChildren &&
                                collect($item['children'])->contains(fn($c) => request()->routeIs($c['route'] ?? '')));
                        $isOpen = $isActive;
                    @endphp

                    @if ($hasChildren)
                        {{-- Accordion item --}}
                        <div class="nav-item has-children {{ $isOpen ? 'open' : '' }}"
                            data-search="{{ strtolower($item['label']) }}">
                            <button class="nav-link nav-accordion-toggle" type="button"
                                aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                                <span class="nav-icon"><i class="{{ $item['icon'] }}"></i></span>
                                <span class="nav-label">{{ $item['label'] }}</span>
                                @if (!empty($item['badge']))
                                    <span class="nav-badge {{ $item['badge']['type'] ?? 'default' }}">
                                        {{ $item['badge']['value'] }}
                                    </span>
                                @endif
                                <span class="nav-arrow"><i class="fa-solid fa-chevron-down"></i></span>
                            </button>
                            <ul class="nav-children" style="{{ $isOpen ? '' : 'display:none' }}">
                                @foreach ($item['children'] as $child)
                                    @php $childActive = request()->routeIs($child['route'] ?? ''); @endphp
                                    <li>
                                        <a href="{{ isset($child['route']) ? route($child['route']) : ($child['url'] ?? '#') }}"
                                           class="nav-child-link {{ $childActive ? 'active' : '' }}"
                                           data-search="{{ strtolower($child['label']) }}">
                                            <span class="child-dot"></span>
                                            {{ $child['label'] }}
                                            @if (!empty($child['badge']))
                                                <span class="nav-badge {{ $child['badge']['type'] ?? 'default' }} ml-auto">
                                                    {{ $child['badge']['value'] }}
                                                </span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        {{-- Simple item --}}
                        <div class="nav-item {{ $isActive ? 'active' : '' }}"
                            data-search="{{ strtolower($item['label']) }}">
                            {{-- <a href="{{ isset($item['route']) ? route($item['route']) : ($item['url'] ?? '#') }}"
                               class="nav-link {{ $isActive ? 'active' : '' }}">
                                <span class="nav-icon"><i class="{{ $item['icon'] }}"></i></span>
                                <span class="nav-label">{{ $item['label'] }}</span>
                                @if (!empty($item['badge']))
                                    <span class="nav-badge {{ $item['badge']['type'] ?? 'default' }}">
                                        {{ $item['badge']['value'] }}
                                    </span>
                                @endif
                            </a> --}}
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- User card --}}
    <div class="sidebar-user">
        <div class="user-avatar">
            @if ($user && $user->avatar)
                <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}">
            @else
                <span>{{ $user ? strtoupper(substr($user->name, 0, 2)) : 'GU' }}</span>
            @endif
            <span class="user-status online"></span>
        </div>
        <div class="user-info">
            <span class="user-name">{{ $user->name ?? 'Guest User' }}</span>
            <span class="user-role">{{ $user->role ?? 'Administrator' }}</span>
        </div>
        {{-- <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           class="user-logout" title="Logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a> --}}
        {{-- <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none"> --}}
        @csrf
        </form>
    </div>

</aside>
