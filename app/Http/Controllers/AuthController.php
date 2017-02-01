<?php

namespace NeoClocking\Http\Controllers;

use Illuminate\Http\Request;
use NeoClocking\Exceptions\UserNotAuthorisedException;
use NeoClocking\Services\AuthenticationService;

/**
 * Class AuthController
 */
class AuthController extends Controller
{
    /**
     * @var AuthenticationService
     */
    protected $authService;

    /**
     * Inject dependencies
     *
     * @param AuthenticationService $authService
     */
    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Show login form
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        return view('auth.show');
    }

    /**
     * Try to login a user
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        try {
            $this->authService->login($request->only('username', 'password'));
        } catch (UserNotAuthorisedException $exception) {
            return back()->withErrors($exception->getMessage());
        }

        return redirect()->route('dashboard');
    }

    /**
     * Logout a user
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        $this->authService->logout();

        return redirect()->route('login.show');
    }

    /**
     * Login as another user
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginAs(Request $request)
    {
        $this->authService->loginAs($request->input('username'));

        return redirect()->route('dashboard');
    }
}
