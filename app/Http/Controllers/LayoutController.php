<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * LayoutController
 * Handles server-side layout preferences (sidebar state, theme, etc.)
 */
class LayoutController extends Controller
{
    /**
     * Persist sidebar collapsed/expanded state in the session.
     * Called via AJAX from layout.js
     *
     * POST /layout/sidebar-state
     */
    public function sidebarState(Request $request): JsonResponse
    {
        $request->validate([
            'collapsed' => 'required|boolean',
        ]);

        session(['sidebar_collapsed' => $request->boolean('collapsed')]);

        return response()->json(['success' => true]);
    }
}
