<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JwtLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_jwt_cookie_and_redirects_to_role_dashboard(): void
    {
        $user = Usuario::factory()->create([
            'email' => 'docente@example.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.teacher', absolute: false));
        $response->assertCookie('jwt_token');
    }
}