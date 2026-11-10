<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Facturacion\Model\Comprobante;
use App\Domain\Facturacion\Port\ComprobanteRepository;

/**
 * Adaptador de persistencia (implementa el puerto ComprobanteRepository).
 * Aqui vive el MAPEO entre el agregado de dominio y la tabla. El dominio nunca
 * importa Eloquent; si se cambia de ORM, solo cambia este archivo.
 */
final class RepositorioComprobanteEloquent implements ComprobanteRepository
{
    public function guardar(Comprobante $c): void
    {
        ComprobanteModel::query()->updateOrCreate(
            ['id' => $c->id],
            [
                'pais'          => $c->pais->value,
                'tipo'          => $c->tipo->value,
                'serie'         => $c->serie,
                'numero'        => $c->numero,
                'ruc_emisor'    => $c->emisor->identificadorFiscal,
                'estado'        => $c->estado()->value,
                'cufe'          => $c->cufe(),
                'xml_doc_id'    => $c->xmlDocumentoId(),
                'total'         => $c->total()->monto,
                'moneda'        => $c->moneda,
                'fecha_emision' => $c->fechaEmision->format('Y-m-d H:i:s'),
                'payload'       => $this->serializar($c),
            ],
        );
    }

    public function porId(string $id): ?Comprobante
    {
        $row = ComprobanteModel::query()->find($id);
        return $row ? $this->hidratar($row) : null;
    }

    public function porClaveNegocio(string $pais, string $serie, string $numero, string $rucEmisor): ?Comprobante
    {
        $row = ComprobanteModel::query()
            ->where(compact('pais', 'serie', 'numero'))
            ->where('ruc_emisor', $rucEmisor)
            ->first();

        return $row ? $this->hidratar($row) : null;
    }

    private function serializar(Comprobante $c): array
    {
        // Snapshot del agregado para reconstruirlo (eventos, lineas, etc.).
        return ['eventos' => $c->eventos()];
    }

    private function hidratar(ComprobanteModel $row): Comprobante
    {
        // TODO: reconstruir el agregado completo desde $row->payload.
        // (En este scaffold se omite el mapeo inverso completo por brevedad.)
        throw new \RuntimeException('Hidratacion completa pendiente en el scaffold.');
    }
}
