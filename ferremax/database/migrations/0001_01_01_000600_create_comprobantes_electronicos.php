<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seguimiento del comprobante electronico de cada venta.
 * El ERP guarda aqui el estado que le reporta el microservicio de facturacion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes_electronicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('venta_id')->unique()->constrained('ventas')->cascadeOnDelete();
            $table->string('pais', 2)->default('PE');
            $table->string('tipo', 20);
            $table->string('serie', 8)->nullable();
            $table->string('numero', 12)->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente|aceptado|observado|rechazado|error
            $table->string('modo', 12)->default('simulado');    // simulado|real
            $table->string('codigo_unico')->nullable();          // CUFE / hash CDR
            $table->string('comprobante_id_externo')->nullable(); // id en el microservicio
            $table->string('xml_url')->nullable();
            $table->string('pdf_url')->nullable();
            $table->string('mensaje')->nullable();
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->json('respuesta')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes_electronicos');
    }
};
