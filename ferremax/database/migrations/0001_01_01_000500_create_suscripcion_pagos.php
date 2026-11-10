<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Facturación de la suscripción SaaS de cada ferretería (tenant).
 * Cada registro es un pago de plan que extiende empresas.plan_vence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('plan', 20);                 // snapshot del plan facturado
            $table->decimal('monto', 12, 2);
            $table->unsignedSmallInteger('meses')->default(1);
            $table->date('periodo_desde');
            $table->date('periodo_hasta');
            $table->date('fecha_pago');
            $table->enum('metodo', ['transferencia', 'tarjeta', 'efectivo', 'deposito', 'yape', 'plin', 'cheque'])->default('transferencia');
            $table->string('referencia', 60)->nullable();
            $table->string('nota')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['empresa_id', 'fecha_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_pagos');
    }
};
