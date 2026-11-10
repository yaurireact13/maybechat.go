<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para el perfil Super Admin (control de toda la plataforma SaaS).
 * - El super admin no pertenece a ninguna empresa: users.empresa_id pasa a ser nullable.
 * - Se agrega el rol 'superadmin' al enum de roles.
 * - Las empresas (tenants) ganan vencimiento de plan y motivo de suspensión.
 */
return new class extends Migration
{
    public function up(): void
    {
        // users.empresa_id -> nullable (el super admin no tiene empresa)
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
        });

        // Rol 'superadmin'
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['superadmin', 'admin', 'vendedor', 'almacenero', 'cajero'])
                ->default('vendedor')->change();
        });

        // Control de suscripción de cada empresa
        Schema::table('empresas', function (Blueprint $table) {
            $table->date('plan_vence')->nullable()->after('plan');
            $table->string('suspendido_motivo')->nullable()->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['plan_vence', 'suspendido_motivo']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['admin', 'vendedor', 'almacenero', 'cajero'])
                ->default('vendedor')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable(false)->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
        });
    }
};
