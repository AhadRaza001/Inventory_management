<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

if (!Breadcrumbs::exists('dashboard')) {
    Breadcrumbs::for('dashboard', function ($trail) {
        $trail->push('Dashboard', route('dashboard'));
    });
}