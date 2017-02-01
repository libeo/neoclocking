<?php

use NeoClocking\Models\UserRole;

if (!function_exists('fractal')) {
    /**
     * @return NeoClocking\Services\FractalService
     */
    function fractal()
    {
        return app(NeoClocking\Services\FractalService::class);
    }
}

if (!function_exists('user')) {
    /**
     * @return NeoClocking\Models\User
     */
    function user()
    {
        return auth()->user();
    }
}

if (!function_exists('is_dredd')) {
    /**
     * @return bool
     */
    function is_dredd()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        return str_contains($_SERVER['HTTP_USER_AGENT'], 'Dredd') && file_exists(base_path('.env.testing'));
    }
}

if (!function_exists('manager_assistant_roles')) {
    /**
     * @return Illuminate\Database\Eloquent\Collection|UserRole[]
     */
    function manager_assistant_roles()
    {
        return UserRole::whereIn('code', [
            UserRole::CODE_ASSISTANT,
            UserRole::CODE_MANAGER,
        ])->get();
    }
}
