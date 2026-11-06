<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Obter as faturas de um utilizador, opcionalmente filtradas por propriedade.
     */
    public function getInvoicesByResident(
        string $residentId,
        int $perPage = 15,
        ?string $propertyId = null
    ): LengthAwarePaginator {
        $query = Invoice::query()
            ->where('resident_id', $residentId);

        // Aplica o filtro da propriedade apenas quando é fornecido.
        if ($propertyId !== null) {
            $query->where('property_id', $propertyId);
        }

        return $query
            ->with([
                'property',
                'consumptions.consumptionType',
                'consumptions.items',
            ])
            ->latest()
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Obter uma fatura específica pelo ID garantindo que pertence ao utilizador.
     */
    public function getInvoiceById(
        string $invoiceId,
        string $residentId
    ): Invoice {
        $invoice = Invoice::query()
            ->where('id', $invoiceId)
            ->where('resident_id', $residentId)
            ->with([
                'property',
                'consumptions.consumptionType',
                'consumptions.items',
            ])
            ->first();

        if (!$invoice) {
            abort(404, 'Fatura não encontrada nos registos do residente.');
        }

        return $invoice;
    }

    /**
     * Criar uma fatura e os respetivos consumos dentro de uma transação.
     */
    public function createInvoiceWithConsumptions(
        string $residentId,
        array $invoiceData,
        array $consumptionsData
    ): Invoice {
        return DB::transaction(function () use (
            $residentId,
            $invoiceData,
            $consumptionsData
        ) {
            // Associa a fatura ao residente autenticado.
            $invoiceData['resident_id'] = $residentId;

            $invoice = Invoice::create($invoiceData);

            // Cria os consumos associados à fatura.
            if (!empty($consumptionsData)) {
                foreach ($consumptionsData as $item) {
                    $consumption = $invoice->consumptions()->create([
                        'property_id'         => $invoice->property_id,
                        'consumption_type_id' => $item['consumption_type_id'],
                        'period_start'        => $invoice->period_start,
                        'period_end'          => $invoice->period_end,
                        'amount'              => $item['amount'],
                        'cost'                => $item['cost'],
                    ]);

                    // Cria os itens individuais de cada consumo.
                    if (!empty($item['items'])) {
                        foreach ($item['items'] as $subItem) {
                            $consumption->items()->create([
                                'name'     => $subItem['name'],
                                'category' => $subItem['category'] ?? null,
                                'amount'   => $subItem['amount'] ?? null,
                                'unit'     => $subItem['unit'] ?? null,
                                'cost'     => $subItem['cost'],
                            ]);
                        }
                    }
                }
            }

            // Devolve a fatura já carregada com as respetivas relações.
            return $invoice->load([
                'property',
                'consumptions.consumptionType',
                'consumptions.items',
            ]);
        });
    }

    /**
     * Apagar uma fatura e os consumos associados.
     */
    public function deleteInvoice(
        string $invoiceId,
        string $residentId
    ): bool {
        // Confirma que a fatura pertence ao residente.
        $invoice = $this->getInvoiceById($invoiceId, $residentId);

        // A eliminação dos registos é feita numa única transação.
        $deleted = DB::transaction(function () use ($invoice) {
            // Garante que os consumos e respetivos itens estão carregados.
            $invoice->loadMissing('consumptions.items');

            foreach ($invoice->consumptions as $consumption) {
                // Apaga primeiro os itens associados ao consumo.
                $consumption->items()->delete();

                // Apaga depois o próprio consumo.
                $consumption->delete();
            }

            // Por fim, apaga a fatura.
            return $invoice->delete();
        });

        // Só depois de a BD confirmar a eliminação, apagar o PDF do bucket.
        if ($deleted && $invoice->file_id) {
            try {
                Storage::disk('r2')->delete(
                    "invoices/{$residentId}/{$invoice->file_id}.pdf"
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $deleted;
    }
}
