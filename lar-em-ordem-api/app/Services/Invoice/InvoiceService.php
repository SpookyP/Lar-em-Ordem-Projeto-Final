<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Services\FileStorage\FileStorageService;
use App\Models\File\File;
use App\Models\User\Resident;

class InvoiceService
{
    public const FILE_CATEGORY = 'invoices';

    public function __construct(private FileStorageService $files) {}

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
        // the PDF (if any) is already stored and validated by StoreInvoiceRequest: only its id is saved here
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
    }

    /**
     * Apagar uma fatura e os consumos associados.
     */
    public function deleteInvoice(string  $invoiceId, string  $residentId): bool
    {
        $invoice = $this->getInvoiceById($invoiceId, $residentId);
        $file = $invoice->file;

        $deleted = DB::transaction(function () use ($invoice) {
            $invoice->consumptions()->delete();

            return $invoice->delete();
        });

        // only after the database confirms: remove the PDF (object in R2 and row in `files`)
        if ($deleted && $file) {
            try {
                $this->files->deleteFile($file);
            } catch (\Throwable $e) {
                report($e); // a fatura já foi apagada, não faz sentido devolver erro ao utilizador
            }
        }

        return $deleted;
    }

    /**
     * Deletes every invoice of a resident and every invoice PDF they uploaded, saved or not
     * (account erasure). Consumptions and their items go by cascade.
     */
    public function deleteAllForResident(Resident $resident): void
    {
        Invoice::where('resident_id', $resident->id)->delete();

        File::where('user_id', $resident->user_id)
            ->where('category', self::FILE_CATEGORY)
            ->whereDoesntHave('invoice')
            ->get()
            ->each(fn (File $file) => $this->files->deleteFile($file));
    }
}