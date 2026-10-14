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
    public function getInvoicesByUser(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::query()
            ->where('user_id', $userId)
            ->with(['property', 'consumptions.consumptionType'])
            ->latest()
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Obter uma fatura específica pelo ID garantindo que pertence ao utilizador.
     */
    public function getInvoiceById(string  $invoiceId, string  $userId): Invoice
    {
        $invoice = Invoice::query()
            ->where('id', $invoiceId)
            ->where('user_id', $userId)
            ->with(['property', 'consumptions.consumptionType'])
            ->first();

        if (!$invoice) {
            abort(404, 'Invoice not found under the user records.');
        }

        return $invoice;
    }

    /**
     * Criar uma fatura e os respetivos consumos dentro de uma transação.
     */
    public function createInvoiceWithConsumptions(string  $userId, array $invoiceData, array $consumptionsData): Invoice
    {
        $fileId  = $invoiceData['file_id'] ?? null;
        $pending = "pending/{$userId}/{$fileId}.pdf";
        $final   = "invoices/{$userId}/{$fileId}.pdf";

        // Move o ficheiro de pending para a pasta definitiva antes de mexer na BD
        if ($fileId) {
            Storage::disk('r2')->move($pending, $final);
        }

        try {
            return DB::transaction(function () use ($userId, $invoiceData, $consumptionsData) {
                $invoiceData['user_id'] = $userId;

                $invoice = Invoice::create($invoiceData);

                if (!empty($consumptionsData)) {
                    $formattedConsumptions = array_map(function (array $item) use ($invoice) {
                        return [
                            'property_id' => $invoice->property_id,
                            'consumption_type_id' => $item['consumption_type_id'],
                            'period_start' => $invoice->period_start,
                            'period_end' => $invoice->period_end,
                            'amount' => $item['amount'],
                            'cost' => $item['cost'],
                        ];
                    }, $consumptionsData);

                    $invoice->consumptions()->createMany($formattedConsumptions);
                }

                return $invoice->load(['property', 'consumptions.consumptionType']);
            });
        } catch (\Throwable $e) {
            // Se a transação da BD falhar, devolve o ficheiro para pending para não o perder
            if ($fileId) {
                Storage::disk('r2')->move($final, $pending);
            }
            throw $e;
        }
    }

    /**
     * Apagar uma fatura e os consumos associados.
     */
    public function deleteInvoice(string  $invoiceId, string  $userId): bool
    {
        $invoice = $this->getInvoiceById($invoiceId, $userId);

        $deleted = DB::transaction(function () use ($invoice) {
            $invoice->consumptions()->delete();

            return $invoice->delete();
        });

        // Só depois de a BD confirmar: apagar o PDF do bucket
        if ($deleted && $invoice->file_id) {
            try {
                Storage::disk('r2')->delete("invoices/{$userId}/{$invoice->file_id}.pdf");
            } catch (\Throwable $e) {
                report($e); // a fatura já foi apagada, não faz sentido devolver erro ao utilizador
            }
        }

        return $deleted;
    }
}
