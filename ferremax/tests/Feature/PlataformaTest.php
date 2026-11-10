<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlataformaTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_self_service_crea_empresa_y_administrador(): void
    {
        $this->post('/registro', [
            'empresa_nombre' => 'Ferretería Nueva', 'moneda' => 'S/', 'impuesto' => 18,
            'name' => 'Dueña', 'email' => 'duena@test.local',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'acepta' => '1',
        ])->assertRedirect();

        $empresa = Empresa::where('nombre', 'Ferretería Nueva')->firstOrFail();
        $this->assertDatabaseHas('users', ['email' => 'duena@test.local', 'empresa_id' => $empresa->id, 'rol' => 'admin']);
    }

    public function test_super_admin_suspende_y_los_usuarios_pierden_el_acceso(): void
    {
        ['e' => $e, 'admin' => $admin] = $this->crearFerreteria();
        $super = $this->superAdmin();

        $this->actingAs($super)->patch("/panel/empresas/{$e->id}/suspender", ['motivo' => 'Falta de pago'])
            ->assertRedirect();
        $this->assertFalse($e->fresh()->activo);

        $this->actingAs($admin->fresh())->get('/dashboard')->assertRedirect('/login');

        // Al reactivar, el acceso vuelve
        $this->actingAs($super)->patch("/panel/empresas/{$e->id}/suspender");
        $this->assertTrue($e->fresh()->activo);
        $this->actingAs(User::find($admin->id))->get('/dashboard')->assertOk();
    }

    public function test_impersonacion_y_retorno_al_panel(): void
    {
        ['e' => $e, 'admin' => $admin] = $this->crearFerreteria();
        $super = $this->superAdmin();

        $this->actingAs($super)->post("/panel/empresas/{$e->id}/impersonar")->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin->fresh());
        $this->get('/dashboard')->assertOk();

        $this->post('/impersonacion/salir')->assertRedirect('/panel/empresas');
        $this->assertAuthenticatedAs($super->fresh());
    }
}
