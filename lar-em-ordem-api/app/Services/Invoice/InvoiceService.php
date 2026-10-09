<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
    }

    /**
     * Apagar uma fatura e os consumos associados.
     */
    public function deleteInvoice(string  $invoiceId, string  $userId): bool
    {
        $invoice = $this->getInvoiceById($invoiceId, $userId);

        return DB::transaction(function () use ($invoice) {
            $invoice->consumptions()->delete();

            return $invoice->delete();
        });
    }
}
