<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Model;

use App\Domain\Facturacion\Enum\EstadoComprobante;
use App\Domain\Facturacion\Enum\Pais;
use App\Domain\Facturacion\Enum\TipoComprobante;
use App\Domain\Facturacion\Model\ValueObject\Dinero;
use App\Domain\Facturacion\Model\ValueObject\Emisor;
use App\Domain\Facturacion\Model\ValueObject\LineaDetalle;
use App\Domain\Facturacion\Model\ValueObject\Receptor;
use App\Domain\Facturacion\Exception\FacturacionException;

/**
 * AGGREGATE ROOT del contexto de Facturacion.
 *
 * Contiene TODA la logica de negocio invariante y agnostica del pais:
 * calculo de totales, transiciones de estado, reglas de anulacion.
 * No conoce SUNAT, DIAN, HTTP, SOAP ni la base de datos (eso vive en Infraestructura).
 */
final class Comprobante
{
    /** @var LineaDetalle[] */
    private array $lineas;
    private EstadoComprobante $estado;
    private ?string $cufe = null;         // codigo unico local (CDR/CUFE/CAE/UUID segun pais)
    private ?string $xmlDocumentoId = null;
    private array $eventos = [];

    public function __construct(
        public readonly string $id,            // UUID interno
        public readonly Pais $pais,
        public readonly TipoComprobante $tipo,
        public readonly string $serie,
        public readonly string $numero,
        public readonly Emisor $emisor,
        public readonly Receptor $receptor,
        array $lineas,
        public readonly \DateTimeImmutable $fechaEmision,
        public readonly string $moneda = 'PEN',
        public readonly ?string $comprobanteAfectadoId = null, // para notas de credito/debito
    ) {
        if ($lineas === []) {
            throw new FacturacionException('El comprobante debe tener al menos una linea.');
        }
        $this->lineas = array_values($lineas);
        $this->estado = EstadoComprobante::PENDIENTE;
    }

    /** @return LineaDetalle[] */
    public function lineas(): array { return $this->lineas; }
    public function estado(): EstadoComprobante { return $this->estado; }
    public function cufe(): ?string { return $this->cufe; }
    public function xmlDocumentoId(): ?string { return $this->xmlDocumentoId; }
    public function eventos(): array { return $this->eventos; }

    public function totalGravado(): Dinero
    {
        $acc = new Dinero(0, $this->moneda);
        foreach ($this->lineas as $l) {
            $acc = $acc->sumar($l->valorVenta());
        }
        return $acc;
    }

    public function totalImpuestos(): Dinero
    {
        $acc = new Dinero(0, $this->moneda);
        foreach ($this->lineas as $l) {
            $acc = $acc->sumar($l->impuesto());
        }
        return $acc;
    }

    public function total(): Dinero
    {
        return $this->totalGravado()->sumar($this->totalImpuestos());
    }

    public function numeroCompleto(): string
    {
        return "{$this->serie}-{$this->numero}";
    }

    // ---- Transiciones de estado (invariantes de negocio) ----

    public function marcarEnviado(): void
    {
        $this->transicionar(EstadoComprobante::ENVIADO, 'Enviado al organismo fiscal');
    }

    public function marcarAceptado(string $cufe, string $xmlDocumentoId): void
    {
        $this->cufe = $cufe;
        $this->xmlDocumentoId = $xmlDocumentoId;
        $this->transicionar(EstadoComprobante::ACEPTADO, "Aceptado. Codigo: {$cufe}");
    }

    public function marcarObservado(string $cufe, string $detalle): void
    {
        $this->cufe = $cufe;
        $this->transicionar(EstadoComprobante::OBSERVADO, $detalle);
    }

    public function marcarRechazado(string $codigo, string $detalle): void
    {
        $this->transicionar(EstadoComprobante::RECHAZADO, "[{$codigo}] {$detalle}");
    }

    public function marcarError(string $detalle): void
    {
        $this->transicionar(EstadoComprobante::ERROR, $detalle);
    }

    public function anular(string $motivo): void
    {
        if (! $this->estado->esFinalExitoso()) {
            throw new FacturacionException('Solo se puede anular un comprobante aceptado.');
        }
        $this->transicionar(EstadoComprobante::ANULADO, "Anulado: {$motivo}");
    }

    private function transicionar(EstadoComprobante $nuevo, string $detalle): void
    {
        $this->estado = $nuevo;
        // Se registra un evento de dominio para auditoria y para el patron Outbox.
        $this->eventos[] = [
            'estado'  => $nuevo->value,
            'detalle' => $detalle,
            'fecha'   => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];
    }
}
