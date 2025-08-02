<?php

namespace App\Gateway\Auth;

use Illuminate\Http\Request;

class AuthenticationGateway
{
    /**
     * Authentication providers
     *
     * @var array
     */
    protected array $providers = [];

    /**
     * Create a new authentication gateway
     *
     * @param array $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * Authenticate a request
     *
     * @param Request $request
     * @return bool
     */
    public function authenticate(Request $request): bool
    {
        // For now, we'll delegate to Laravel's auth system
        return auth()->check();
    }

    /**
     * Get the authenticated user
     *
     * @return mixed
     */
    public function user()
    {
        return auth()->user();
    }

    /**
     * Check if user has permission
     *
     * @param string $permission
     * @return bool
     */
    public function can(string $permission): bool
    {
        $user = $this->user();
        return $user ? $user->can($permission) : false;
    }

    /**
     * Add an authentication provider
     *
     * @param string $name
     * @param mixed $provider
     */
    public function addProvider(string $name, $provider): void
    {
        $this->providers[$name] = $provider;
    }

    /**
     * Get authentication provider
     *
     * @param string $name
     * @return mixed|null
     */
    public function getProvider(string $name)
    {
        return $this->providers[$name] ?? null;
    }
}