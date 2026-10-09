<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiLoginValidationTest extends TestCase
{
    public function test_login_validation_errors_return_unprocessable_json(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
