<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Domain tests use an enrolled administrator; authentication-specific tests
        // explicitly clear this fixture secret when testing mandatory enrollment.
        User::creating(function ($user) {
            if ($user->role === 'admin' && ! $user->app_authentication_secret) {
                $user->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
            }
        });
    }
}
