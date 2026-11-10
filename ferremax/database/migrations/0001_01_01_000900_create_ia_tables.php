<?php

use App\Services\Ia\IaService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asistente de reportes IA y Chat IA.
 * - ia_configs: proveedor/modelo/clave (cifrada) por empresa.
 * - ia_consultas: historial y favoritas del asistente de reportes.
 * - vistas vb_ia_*: capa de lectura que es lo ÚNICO que ve la IA (se filtra por empresa al ejecutar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ia_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresas')->cascadeOnDelete();
            $table->string('proveedor', 30)->default('gemini');
            $table->string('url')->nullable();
            $table->string('modelo', 100)->nullable();
            $table->text('clave')->nullable(); // cifrada con APP_KEY
            $table->boolean('resumen')->default(true);
            $table->timestamps();
        });

        Schema::create('ia_consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('pregunta', 500);
            $table->string('titulo', 160)->nullable();
            $table->text('sql_texto');
            $table->string('grafico')->nullable();
            $table->boolean('favorito')->default(false);
            $table->unsignedInteger('veces')->default(1);
            $table->dateTime('ultimo_uso')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'user_id', 'favorito']);
        });

        IaService::instalarVistas(true);
    }

    public function down(): void
    {
        IaService::quitarVistas();
        Schema::dropIfExists('ia_consultas');
        Schema::dropIfExists('ia_configs');
    }
};
