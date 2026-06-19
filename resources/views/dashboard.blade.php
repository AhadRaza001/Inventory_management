@extends('layout.app')

@section('title', 'Dashboard')

@section('content')

<div class="dashboard-header">
    <div>
        <h1 class="page-title">Good morning, {{ auth()->user()?->name ?? 'there' }} 👋</h1>
        <p class="page-subtitle">Here's what's happening with your finances today.</p>
    </div>
    <div class="dashboard-actions">
        <button class="btn btn-outline">
            <i class="fa-solid fa-arrow-down-to-bracket"></i> Export
        </button>
        <button class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Transaction
        </button>
    </div>
</div>

{{-- Stats Cards --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div class="stat-body">
            <span class="stat-label">Total Revenue</span>
            <span class="stat-value">$84,250</span>
            <span class="stat-change up"><i class="fa-solid fa-arrow-up"></i> 12.5% vs last month</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-arrow-trend-down"></i></div>
        <div class="stat-body">
            <span class="stat-label">Total Expenses</span>
            <span class="stat-value">$31,480</span>
            <span class="stat-change down"><i class="fa-solid fa-arrow-up"></i> 3.2% vs last month</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-wallet"></i></div>
        <div class="stat-body">
            <span class="stat-label">Net Balance</span>
            <span class="stat-value">$52,770</span>
            <span class="stat-change up"><i class="fa-solid fa-arrow-up"></i> 8.1% vs last month</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="stat-body">
            <span class="stat-label">Pending Invoices</span>
            <span class="stat-value">$12,300</span>
            <span class="stat-change neutral"><i class="fa-solid fa-clock"></i> 5 invoices pending</span>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.dashboard-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 28px; gap: 16px; flex-wrap: wrap;
}
.page-title { font-family: 'Space Grotesk', sans-serif; font-size: 24px; font-weight: 700; color: var(--text-primary); }
.page-subtitle { font-size: 14px; color: var(--text-secondary); margin-top: 4px; }
.dashboard-actions { display: flex; gap: 10px; }
.btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 9px 18px; border-radius: var(--radius-md); font-size: 13.5px;
    font-weight: 600; font-family: 'DM Sans', sans-serif; transition: all var(--transition); cursor: pointer;
}
.btn-primary { background: var(--accent); color: #fff; border: 1.5px solid transparent; }
.btn-primary:hover { background: #2563eb; box-shadow: 0 4px 14px var(--accent-glow); }
.btn-outline { background: transparent; color: var(--text-secondary); border: 1.5px solid var(--topbar-border); }
.btn-outline:hover { background: var(--bg-surface-2); color: var(--text-primary); }
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
}
.stat-card {
    background: var(--bg-surface); border-radius: var(--radius-lg);
    padding: 20px; display: flex; align-items: flex-start; gap: 14px;
    border: 1px solid var(--topbar-border); transition: box-shadow var(--transition);
}
.stat-card:hover { box-shadow: var(--shadow-md); }
.stat-icon {
    width: 44px; height: 44px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.stat-icon.green  { background: rgba(34,197,94,.1);   color: var(--success); }
.stat-icon.red    { background: rgba(239,68,68,.1);    color: var(--danger);  }
.stat-icon.blue   { background: rgba(59,130,246,.1);   color: var(--accent);  }
.stat-icon.purple { background: rgba(124,58,237,.1);   color: #7c3aed;        }
.stat-body { flex: 1; }
.stat-label { display: block; font-size: 12px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; }
.stat-value { display: block; font-size: 26px; font-weight: 700; font-family: 'Space Grotesk', sans-serif; color: var(--text-primary); margin: 4px 0; line-height: 1; }
.stat-change { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 500; }
.stat-change.up      { color: var(--success); }
.stat-change.down    { color: var(--danger);  }
.stat-change.neutral { color: var(--text-muted); }
</style>
@endpush
