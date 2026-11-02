<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Obter as faturas de um utilizador.
     */
    public function getInvoicesByResident(string $residentId, int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::query()
            ->where('resident_id', $residentId)
            ->with(['property', 'consumptions.consumptionType', 'consumptions.items'])
            ->latest()
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Obter uma fatura específica pelo ID garantindo que pertence ao utilizador.
     */
    public function getInvoiceById(string  $invoiceId, string  $residentId): Invoice
    {
        $invoice = Invoice::query()
            ->where('id', $invoiceId)
            ->where('resident_id', $residentId)
            ->with(['property', 'consumptions.consumptionType', 'consumptions.items'])
            ->first();

        if (!$invoice) {
            abort(404, 'Invoice not found under the resident records.');
        }

        return $invoice;
    }

    /**
     * Criar uma fatura e os respetivos consumos dentro de uma transação.
     */
    public function createInvoiceWithConsumptions(string $residentId, array $invoiceData, array $consumptionsData): Invoice
    {
        $fileId  = $invoiceData['file_id'] ?? null;
        $pending = "pending/{$residentId}/{$fileId}.pdf";
        $final   = "invoices/{$residentId}/{$fileId}.pdf";

        // Move o ficheiro de pending para a pasta definitiva de forma segura
        if ($fileId) {
            Storage::disk('r2')->makeDirectory("invoices/{$residentId}");
            
            if (Storage::disk('r2')->exists($pending)) {
                Storage::disk('r2')->put($final, Storage::disk('r2')->get($pending));
                Storage::disk('r2')->delete($pending);
            }
        }

        try {
            return DB::transaction(function () use ($residentId, $invoiceData, $consumptionsData) {
                $invoiceData['resident_id'] = $residentId;

                $invoice = Invoice::create($invoiceData);

                if (!empty($consumptionsData)) {
                    foreach ($consumptionsData as $item) {
                        $consumption = $invoice->consumptions()->create([
                            'property_id'           => $invoice->property_id,
                            'consumption_type_id'   => $item['consumption_type_id'],
                            'period_start'          => $invoice->period_start,
                            'period_end'            => $invoice->period_end,
                            'amount'                => $item['amount'],
                            'cost'                  => $item['cost'],
                        ]);

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

                return $invoice->load(['property', 'consumptions.consumptionType', 'consumptions.items']);
            });
        } catch (\Throwable $e) {
            // Se falhar na BD, devolve o ficheiro para pending
            if ($fileId && Storage::disk('r2')->exists($final)) {
                Storage::disk('r2')->put($pending, Storage::disk('r2')->get($final));
                Storage::disk('r2')->delete($final);
            }
            throw $e;
        }
    }

    /**
     * Apagar uma fatura e os consumos associados.
     */
    public function deleteInvoice(string  $invoiceId, string  $residentId): bool
    {
        $invoice = $this->getInvoiceById($invoiceId, $residentId);

        $deleted = DB::transaction(function () use ($invoice) {
            $invoice->consumptions()->delete();

            return $invoice->delete();
        });

        // Só depois de a BD confirmar: apagar o PDF do bucket
        if ($deleted && $invoice->file_id) {
            try {
                Storage::disk('r2')->delete("invoices/{$residentId}/{$invoice->file_id}.pdf");
            } catch (\Throwable $e) {
                report($e); // a fatura já foi apagada, não faz sentido devolver erro ao utilizador
            }
        }

        return $deleted;
    }
}