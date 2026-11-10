<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tablas de soporte: auditoria, outbox de eventos, idempotencia y credenciales del emisor. */
return new class extends Migration
{
    public function up(): void
    {
        // Auditoria append-only (trazabilidad completa)
        Schema::create('comprobante_eventos', function (Blueprint $table) {
            $table->id();
            $table->uuid('comprobante_id')->index();
            $table->string('accion', 40);
            $table->string('estado', 20);
            $table->json('metadatos')->nullable();   // request/response/codigo del organismo
            $table->timestamp('creado_en')->useCurrent();
        });

        // Patron OUTBOX: eventos a publicar en la cola (webhooks al ERP / mensajeria)
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('comprobante_id')->index();
            $table->string('tipo_evento', 60);
            $table->json('payload');
            $table->string('estado', 15)->default('pendiente'); // pendiente|publicado|error
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->timestamp('disponible_en')->nullable();      // backoff
            $table->timestamps();
        });

        // Idempotencia HTTP
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->string('clave')->primary();
            $table->unsignedSmallInteger('status');
            $table->longText('respuesta');
            $table->timestamp('creado_en')->useCurrent();
        });

        // Credenciales/certificados por emisor y pais (cifrados en reposo)
        Schema::create('credenciales_emisor', function (Blueprint $table) {
            $table->id();
            $table->string('pais', 2);
            $table->string('ruc_emisor', 20);
            $table->text('certificado_cifrado')->nullable(); // .pfx/.pem cifrado
            $table->text('clave_sol_cifrada')->nullable();   // usuario/clave SOL (SUNAT), tokens, etc.
            $table->json('parametros')->nullable();
            $table->timestamps();
            $table->unique(['pais', 'ruc_emisor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credenciales_emisor');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('outbox_messages');
        Schema::dropIfExists('comprobante_eventos');
    }
};
