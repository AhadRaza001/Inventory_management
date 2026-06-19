<?php

/**
 * sidebar.php — Dynamic sidebar menu configuration
 *
 * Structure:
 *  menus[]
 *    label   => Group heading text
 *    items[] => Menu items
 *      label     => Display label
 *      icon      => FontAwesome class (e.g. 'fa-solid fa-house')
 *      route     => Named Laravel route (optional)
 *      url       => Direct URL fallback (optional)
 *      badge     => ['value' => '3', 'type' => 'danger|warning|success|info']
 *      children[] => Sub-menu items (same structure, no nesting beyond 1 level)
 */

return [
    'menus' => [

        // ── MAIN ───────────────────────────────────────────────────────────
        [
            'label' => 'Main',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon'  => 'fa-solid fa-chart-pie',
                    'route' => 'dashboard',
                ],
                [
                    'label' => 'Analytics',
                    'icon'  => 'fa-solid fa-chart-line',
                    'route' => 'analytics.index',
                ],
            ],
        ],

        // ── FINANCE ────────────────────────────────────────────────────────
        [
            'label' => 'Finance',
            'items' => [
                [
                    'label'    => 'Transactions',
                    'icon'     => 'fa-solid fa-arrow-right-arrow-left',
                    'badge'    => ['value' => 'New', 'type' => 'success'],
                    'children' => [
                        ['label' => 'All Transactions', 'route' => 'transactions.index'],
                        ['label' => 'Income',           'route' => 'transactions.income'],
                        ['label' => 'Expenses',         'route' => 'transactions.expenses'],
                        ['label' => 'Transfers',        'route' => 'transactions.transfers'],
                    ],
                ],
                [
                    'label'    => 'Accounts',
                    'icon'     => 'fa-solid fa-building-columns',
                    'children' => [
                        ['label' => 'Bank Accounts', 'route' => 'accounts.bank'],
                        ['label' => 'Credit Cards',  'route' => 'accounts.cards'],
                        ['label' => 'Wallets',       'route' => 'accounts.wallets'],
                    ],
                ],
                [
                    'label'    => 'Invoices',
                    'icon'     => 'fa-solid fa-file-invoice-dollar',
                    'badge'    => ['value' => '5', 'type' => 'danger'],
                    'children' => [
                        ['label' => 'All Invoices',   'route' => 'invoices.index'],
                        ['label' => 'Create Invoice', 'route' => 'invoices.create'],
                        ['label' => 'Recurring',      'route' => 'invoices.recurring'],
                        ['label' => 'Overdue',        'route' => 'invoices.overdue', 'badge' => ['value' => '2', 'type' => 'danger']],
                    ],
                ],
                [
                    'label'    => 'Budgets',
                    'icon'     => 'fa-solid fa-wallet',
                    'children' => [
                        ['label' => 'Overview',    'route' => 'budgets.index'],
                        ['label' => 'Categories',  'route' => 'budgets.categories'],
                        ['label' => 'Limits',      'route' => 'budgets.limits'],
                    ],
                ],
                [
                    'label' => 'Reports',
                    'icon'  => 'fa-solid fa-chart-bar',
                    'route' => 'reports.index',
                ],
            ],
        ],

        // ── PAYMENTS ───────────────────────────────────────────────────────
        [
            'label' => 'Payments',
            'items' => [
                [
                    'label' => 'Send Money',
                    'icon'  => 'fa-solid fa-paper-plane',
                    'route' => 'payments.send',
                ],
                [
                    'label'    => 'Payment Methods',
                    'icon'     => 'fa-solid fa-credit-card',
                    'children' => [
                        ['label' => 'Cards',       'route' => 'payment-methods.cards'],
                        ['label' => 'Bank Links',  'route' => 'payment-methods.bank'],
                        ['label' => 'Crypto',      'route' => 'payment-methods.crypto'],
                    ],
                ],
                [
                    'label' => 'Subscriptions',
                    'icon'  => 'fa-solid fa-rotate',
                    'route' => 'subscriptions.index',
                ],
            ],
        ],

        // ── MANAGEMENT ─────────────────────────────────────────────────────
        [
            'label' => 'Management',
            'items' => [
                [
                    'label'    => 'Team',
                    'icon'     => 'fa-solid fa-users',
                    'children' => [
                        ['label' => 'Members',     'route' => 'team.members'],
                        ['label' => 'Roles',       'route' => 'team.roles'],
                        ['label' => 'Permissions', 'route' => 'team.permissions'],
                    ],
                ],
                [
                    'label' => 'Clients',
                    'icon'  => 'fa-solid fa-briefcase',
                    'route' => 'clients.index',
                ],
                [
                    'label' => 'Vendors',
                    'icon'  => 'fa-solid fa-store',
                    'route' => 'vendors.index',
                ],
            ],
        ],

        // ── SYSTEM ─────────────────────────────────────────────────────────
        [
            'label' => 'System',
            'items' => [
                [
                    'label' => 'Settings',
                    'icon'  => 'fa-solid fa-gear',
                    'route' => 'settings.index',
                ],
                [
                    'label' => 'Audit Log',
                    'icon'  => 'fa-solid fa-shield-halved',
                    'route' => 'audit.index',
                ],
                [
                    'label' => 'Help & Docs',
                    'icon'  => 'fa-solid fa-circle-question',
                    'url'   => 'https://docs.yourapp.com',
                ],
            ],
        ],

    ],
];
