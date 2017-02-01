<?php

namespace NeoClocking\Http\Controllers;

use NeoClocking\Services\AuthenticationService;

/**
 * Class DashboardController
 */
class DashboardController extends Controller
{
    /**
     * Show the dashboard
     *
     * @param AuthenticationService $authService
     * @return \Illuminate\View\View
     */
    public function show(AuthenticationService $authService)
    {
        $user = $authService->currentUser();
        return view('dashboard.index')->with(compact('user'));
    }
}
