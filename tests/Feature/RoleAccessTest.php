<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_users_to_their_role_panel(): void
    {
        $user = Usuario::factory()->create([
            'role' => 'teacher',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('dashboard.teacher', absolute: false));
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $user = Usuario::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/panel/admin');

        $response->assertOk();
    }

    public function test_student_cannot_access_admin_panel(): void
    {
        $user = Usuario::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($user)->get('/panel/admin');

        $response->assertForbidden();
    }
}