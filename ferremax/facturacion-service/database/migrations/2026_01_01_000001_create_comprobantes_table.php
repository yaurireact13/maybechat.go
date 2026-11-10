<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('pais', 2);
            $table->string('tipo', 20);
            $table->string('serie', 8);
            $table->string('numero', 12);
            $table->string('ruc_emisor', 20);
            $table->string('estado', 20)->index();
            $table->string('cufe')->nullable();          // codigo unico (CDR/CUFE/CAE/UUID)
            $table->string('xml_doc_id')->nullable();     // referencia al XML en el almacen
            $table->decimal('total', 14, 2)->default(0);
            $table->string('moneda', 3)->default('PEN');
            $table->dateTime('fecha_emision');
            $table->json('payload')->nullable();
            $table->timestamps();

            // Clave de negocio unica por emisor (idempotencia a nivel de datos)
            $table->unique(['pais', 'ruc_emisor', 'serie', 'numero'], 'uq_comprobante_negocio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
