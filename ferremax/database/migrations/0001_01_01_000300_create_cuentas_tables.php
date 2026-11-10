<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuentas por Cobrar / Pagar
 * - Saldos y vencimiento en ventas y compras.
 * - Tabla de pagos (cobros a clientes / pagos a proveedores) con abonos parciales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('saldo', 12, 2)->default(0)->after('total');
            $table->date('fecha_vencimiento')->nullable()->after('saldo');
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->boolean('a_credito')->default(false)->after('total');
            $table->decimal('saldo', 12, 2)->default(0)->after('a_credito');
            $table->date('fecha_vencimiento')->nullable()->after('saldo');
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->enum('tipo', ['cobro', 'pago']); // cobro = cliente nos paga; pago = pagamos a proveedor
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('compra_id')->nullable()->constrained('compras')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('monto', 12, 2);
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia', 'yape', 'plin', 'cheque', 'deposito'])->default('efectivo');
            $table->dateTime('fecha');
            $table->string('referencia', 60)->nullable();
            $table->string('nota')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');

        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn(['a_credito', 'saldo', 'fecha_vencimiento']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['saldo', 'fecha_vencimiento']);
        });
    }
};
