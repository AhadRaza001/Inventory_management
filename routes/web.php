<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


// Route::middleware('auth')->post('/layout/sidebar-state', [LayoutController::class, 'sidebarState'])
//     ->name('layout.sidebar-state');

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });
// Route::get('/customer', [CustomerController::class,'index']);
require __DIR__ . '/auth.php';



// Layout AJAX endpoint (sidebar state persistence)
Route::middleware('auth')->post('/layout/sidebar-state', [LayoutController::class, 'sidebarState'])
    ->name('layout.sidebar-state');

// Dashboard
Route::get('/', function () {
    return view('welcome');
});
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ── Finance ───────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('finance')->group(function () {
    Route::get('/transactions',          fn() => view('finance.transactions.index'))->name('transactions.index');
    Route::get('/transactions/income',   fn() => view('finance.transactions.income'))->name('transactions.income');
    Route::get('/transactions/expenses', fn() => view('finance.transactions.expenses'))->name('transactions.expenses');
    Route::get('/transactions/transfers', fn() => view('finance.transactions.transfers'))->name('transactions.transfers');

    Route::get('/accounts/bank',         fn() => view('finance.accounts.bank'))->name('accounts.bank');
    Route::get('/accounts/cards',        fn() => view('finance.accounts.cards'))->name('accounts.cards');
    Route::get('/accounts/wallets',      fn() => view('finance.accounts.wallets'))->name('accounts.wallets');

    Route::get('/invoices',              fn() => view('finance.invoices.index'))->name('invoices.index');
    Route::get('/invoices/create',       fn() => view('finance.invoices.create'))->name('invoices.create');
    Route::get('/invoices/recurring',    fn() => view('finance.invoices.recurring'))->name('invoices.recurring');
    Route::get('/invoices/overdue',      fn() => view('finance.invoices.overdue'))->name('invoices.overdue');

    Route::get('/budgets',               fn() => view('finance.budgets.index'))->name('budgets.index');
    Route::get('/budgets/categories',    fn() => view('finance.budgets.categories'))->name('budgets.categories');
    Route::get('/budgets/limits',        fn() => view('finance.budgets.limits'))->name('budgets.limits');

    Route::get('/reports',               fn() => view('finance.reports.index'))->name('reports.index');
    Route::get('/analytics',             fn() => view('finance.analytics.index'))->name('analytics.index');
});

// ── Payments ──────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('payments')->group(function () {
    Route::get('/send',                        fn() => view('payments.send'))->name('payments.send');
    Route::get('/methods/cards',               fn() => view('payments.methods.cards'))->name('payment-methods.cards');
    Route::get('/methods/bank',                fn() => view('payments.methods.bank'))->name('payment-methods.bank');
    Route::get('/methods/crypto',              fn() => view('payments.methods.crypto'))->name('payment-methods.crypto');
    Route::get('/subscriptions',               fn() => view('payments.subscriptions'))->name('subscriptions.index');
});

// ── Management ────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/team/members',    fn() => view('team.members'))->name('team.members');
    Route::get('/team/roles',      fn() => view('team.roles'))->name('team.roles');
    Route::get('/team/permissions', fn() => view('team.permissions'))->name('team.permissions');
    Route::get('/clients',         fn() => view('clients.index'))->name('clients.index');
    Route::get('/vendors',         fn() => view('vendors.index'))->name('vendors.index');
});

// ── System ────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/settings',        fn() => view('settings.index'))->name('settings.index');
    Route::get('/audit',           fn() => view('audit.index'))->name('audit.index');
    Route::get('/notifications',   fn() => view('notifications.index'))->name('notifications.index');
    Route::get('/billing',         fn() => view('billing.index'))->name('billing.index');
    // Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.show');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/show', [ProfileController::class, 'edit'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});