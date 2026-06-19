{{--
    Topbar Partial
    Usage: @include('layouts.partials.topbar')
--}}
<header class="topbar" id="topbar">

    {{-- Left: Toggle + Breadcrumb --}}
    <div class="topbar-left">
        <button class="topbar-toggle" id="sidebarToggle" title="Toggle sidebar">
            <span></span><span></span><span></span>
        </button>

        <nav class="breadcrumb-nav" aria-label="Breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">
                        <i class="fa-solid fa-house-chimney"></i>
                    </a>
                </li>
                @foreach(Breadcrumbs::generate('dashboard') as $breadcrumb)
                    @if($loop->last)
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $breadcrumb->title }}
                        </li>
                    @else
                        <li class="breadcrumb-item">
                            <a href="{{ $breadcrumb->url }}">{{ $breadcrumb->title }}</a>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>

    {{-- Right: Actions --}}
    <div class="topbar-right">

        {{-- Global Search --}}
        <div class="topbar-search" id="globalSearchWrapper">
            <button class="topbar-search-trigger" id="globalSearchTrigger" title="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <div class="global-search-box" id="globalSearchBox">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="globalSearchInput" placeholder="Search transactions, accounts…">
                <kbd>ESC</kbd>
            </div>
        </div>

        {{-- Theme Toggle --}}
        <button class="topbar-btn theme-toggle" id="themeToggle" title="Toggle theme">
            <i class="fa-solid fa-moon" id="themeIcon"></i>
        </button>

        {{-- Notifications --}}
        <div class="topbar-dropdown" id="notifDropdown">
            <button class="topbar-btn has-badge" id="notifBtn" title="Notifications">
                <i class="fa-solid fa-bell"></i>
                <span class="badge-dot" id="notifBadge">3</span>
            </button>
            <div class="dropdown-panel notif-panel" id="notifPanel">
                <div class="panel-header">
                    <span>Notifications</span>
                    <button class="mark-all-read" id="markAllRead">Mark all read</button>
                </div>
                <ul class="notif-list" id="notifList">
                    <li class="notif-item unread">
                        <div class="notif-icon success"><i class="fa-solid fa-arrow-down"></i></div>
                        <div class="notif-body">
                            <p>Payment received <strong>$4,250</strong></p>
                            <span>2 min ago</span>
                        </div>
                    </li>
                    <li class="notif-item unread">
                        <div class="notif-icon warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="notif-body">
                            <p>Budget limit at <strong>85%</strong> for Q2</p>
                            <span>1 hr ago</span>
                        </div>
                    </li>
                    <li class="notif-item unread">
                        <div class="notif-icon info"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <div class="notif-body">
                            <p>Invoice <strong>#INV-2024</strong> overdue</p>
                            <span>3 hrs ago</span>
                        </div>
                    </li>
                    <li class="notif-item">
                        <div class="notif-icon default"><i class="fa-solid fa-user-plus"></i></div>
                        <div class="notif-body">
                            <p>New team member <strong>joined</strong></p>
                            <span>Yesterday</span>
                        </div>
                    </li>
                </ul>
                <div class="panel-footer">
                    <a href="{{ route('notifications.index') }}">View all notifications</a>
                </div>
            </div>
        </div>

        {{-- User Dropdown --}}
        <div class="topbar-dropdown" id="userDropdown">
            <button class="topbar-user-btn" id="userBtn">
                <div class="topbar-avatar">
                    @if(auth()->user()?->avatar)
                        <img src="{{ asset('storage/'.auth()->user()->avatar) }}" alt="">
                    @else
                        <span>{{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 2)) }}</span>
                    @endif
                </div>
                <div class="topbar-user-info">
                    <span class="topbar-user-name">{{ auth()->user()?->name ?? 'Guest' }}</span>
                    <span class="topbar-user-role">{{ auth()->user()?->role ?? 'Admin' }}</span>
                </div>
                <i class="fa-solid fa-chevron-down topbar-chevron"></i>
            </button>
            <div class="dropdown-panel user-panel" id="userPanel">
                <div class="user-panel-header">
                    <div class="user-panel-avatar">
                        <span>{{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 2)) }}</span>
                    </div>
                    <div>
                        <p class="up-name">{{ auth()->user()?->name ?? 'Guest User' }}</p>
                        <p class="up-email">{{ auth()->user()?->email ?? 'guest@example.com' }}</p>
                    </div>
                </div>
                <ul class="user-panel-menu">
                    <li><a href="{{ route('profile.show') }}"><i class="fa-solid fa-user"></i> My Profile</a></li>
                    <li><a href="{{ route('settings.index') }}"><i class="fa-solid fa-gear"></i> Settings</a></li>
                    <li><a href="{{ route('billing.index') }}"><i class="fa-solid fa-credit-card"></i> Billing</a></li>
                    <li class="divider"></li>
                    <li>
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form-top').submit();"
                           class="logout-link">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
                        </a>
                        <form id="logout-form-top" action="{{ route('logout') }}" method="POST" hidden>@csrf</form>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</header>
