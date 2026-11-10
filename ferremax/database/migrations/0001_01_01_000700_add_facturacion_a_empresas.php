<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ajustes de facturacion electronica por empresa (tenant). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('factura_activa')->default(true)->after('impuesto');
            $table->string('factura_modo', 12)->default('simulado')->after('factura_activa'); // simulado | real
            $table->string('factura_pais', 2)->default('PE')->after('factura_modo');
            $table->string('factura_serie_factura', 8)->default('F001')->after('factura_pais');
            $table->string('factura_serie_boleta', 8)->default('B001')->after('factura_serie_factura');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['factura_activa', 'factura_modo', 'factura_pais', 'factura_serie_factura', 'factura_serie_boleta']);
        });
    }
};
