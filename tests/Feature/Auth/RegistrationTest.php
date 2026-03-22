<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    Notification::fake();
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $password = Str::random(24).'Aa1!';

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration screen is unavailable when public registration is disabled', function () {
    config(['auth.allow_public_registration' => false]);

    $response = $this->get('/register');

    $response->assertNotFound();
});

test('registration submission is unavailable when public registration is disabled', function () {
    config(['auth.allow_public_registration' => false]);
    $password = Str::random(24).'Aa1!';

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'disabled@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertNotFound();
    $this->assertGuest();
});
