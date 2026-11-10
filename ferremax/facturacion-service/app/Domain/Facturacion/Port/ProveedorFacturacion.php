<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Port;

use App\Domain\Facturacion\Enum\Pais;
use App\Domain\Facturacion\Model\Comprobante;
use App\Domain\Facturacion\Port\Resultado\EstadoRemoto;
use App\Domain\Facturacion\Port\Resultado\ResultadoEmision;

/**
 * ================== PUERTO PRINCIPAL (Strategy / Adapter) ==================
 *
 * Interfaz COMUN que todo pais debe implementar. La capa de Aplicacion depende
 * SOLO de este contrato, nunca de una implementacion concreta (SUNAT, DIAN...).
 *
 * Incorporar un pais nuevo = crear una clase que implemente esta interfaz y
 * registrarla en config/facturacion.php. No se toca el dominio ni los casos de uso
 * (Principio Abierto/Cerrado y de Inversion de Dependencias).
 *
 * Cada metodo recibe/devuelve tipos del DOMINIO (Comprobante, ResultadoEmision),
 * jamas tipos crudos de SUNAT/DIAN. Esa traduccion es responsabilidad del adaptador.
 */
interface ProveedorFacturacion
{
    /** Pais que atiende este adaptador. */
    public function pais(): Pais;

    /** Emite una factura o boleta ante el organismo fiscal. */
    public function emitirFactura(Comprobante $comprobante): ResultadoEmision;

    /** Emite una nota de credito que afecta a un comprobante previo. */
    public function emitirNotaCredito(Comprobante $notaCredito): ResultadoEmision;

    /** Anula / da de baja un comprobante ya aceptado. */
    public function anularComprobante(Comprobante $comprobante, string $motivo): ResultadoEmision;

    /** Consulta el estado actual de un comprobante ante el organismo. */
    public function consultarEstado(Comprobante $comprobante): EstadoRemoto;
}
