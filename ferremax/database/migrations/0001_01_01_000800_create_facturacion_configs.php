<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuracion del EMISOR de facturacion electronica por empresa:
 * datos fiscales, ambiente del organismo y credenciales (cifradas en reposo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturacion_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresas')->cascadeOnDelete();

            // Ambiente del organismo (SUNAT: beta = homologacion, produccion = real)
            $table->string('ambiente', 15)->default('beta'); // beta | produccion

            // Datos del emisor (pueden diferir/afinar los de la empresa)
            $table->string('ruc', 20)->nullable();
            $table->string('razon_social')->nullable();
            $table->string('nombre_comercial')->nullable();
            $table->string('direccion_fiscal')->nullable();
            $table->string('ubigeo', 10)->nullable();

            // Credenciales SUNAT (SOL) — cifradas por el modelo
            $table->string('usuario_sol')->nullable();
            $table->text('clave_sol')->nullable();

            // Certificado digital (.pfx/.pem) — archivo fuera de public + clave cifrada
            $table->string('certificado_path')->nullable();
            $table->string('certificado_nombre')->nullable();
            $table->text('clave_certificado')->nullable();
            $table->date('certificado_vence')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturacion_configs');
    }
};
