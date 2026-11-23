<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Invoice;
use App\Models\File\File;
use App\Models\Property\Property;
use App\Models\User\Resident;
use App\Models\User\User;
use App\Services\FileStorage\FileStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class InvoiceService
{
    public const FILE_CATEGORY = 'invoices';

    public function __construct(private FileStorageService $files){}

    public function getInvoicesByResident(
        string $residentId,
        int $perPage = 15,
        ?string $propertyId = null
    ): LengthAwarePaginator {
        $query = Invoice::query()
            ->where('resident_id', $residentId);

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
     * The resident's invoice with that id; a 404 if it doesn't exist or belongs to someone else.
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
                'file',
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
     * Registers an invoice with its consumptions and, if one came, the PDF.
     * The PDF is only stored in R2 now, when the invoice is registered.
     * If registering fails the PDF is deleted, so no file is left without an invoice.
     */
    public function createInvoiceWithConsumptions(
        User $user,
        array $invoiceData,
        array $consumptionsData,
        ?UploadedFile $pdf = null
    ): Invoice {
        $file = $pdf ? $this->files->uploadUserFile($user, $pdf, self::FILE_CATEGORY) : null;

        try {
            return $this->saveInvoice(
                $user->resident->id,
                $invoiceData + ['file_id' => $file?->id],
                $consumptionsData
            );
        } catch (Throwable $e) {
            if ($file) {
                $this->files->deleteFile($file);
            }

            throw $e;
        }
    }

    /**
     * Creates the invoice and its consumptions in one transaction.
     */
    private function saveInvoice(
        string $residentId,
        array $invoiceData,
        array $consumptionsData
    ): Invoice {
        return DB::transaction(function () use (
            $residentId,
            $invoiceData,
            $consumptionsData
        ) {
            $invoiceData['resident_id'] = $residentId;

            $invoice = Invoice::create($invoiceData);

            // Criar os consumos associados à fatura.
            if (!empty($consumptionsData)) {
                foreach ($consumptionsData as $item) {
                    $consumption = $invoice->consumptions()->create([
                        // a consumption copies the invoice's home and period: the benchmarks read them from it
                        'property_id' => $invoice->property_id,
                        'consumption_type_id' => $item['consumption_type_id'],
                        'period_start' => $invoice->period_start,
                        'period_end' => $invoice->period_end,
                        'amount' => $item['amount'],
                        'cost' => $item['cost'],
                    ]);

                    if (!empty($item['items'])) {
                        foreach ($item['items'] as $subItem) {
                            $consumption->items()->create([
                                'name' => $subItem['name'],
                                'category' => $subItem['category'] ?? null,
                                'amount' => $subItem['amount'] ?? null,
                                'unit' => $subItem['unit'] ?? null,
                                'cost' => $subItem['cost'],
                            ]);
                        }
                    }
                }
            }

            return $invoice->load([
                'property',
                'file',
                'consumptions.consumptionType',
                'consumptions.items',
            ]);
        });
    }

    /**
     * Deletes an invoice with its consumptions and items, and then its PDF.
     * A failure deleting the PDF is only reported: the invoice is already gone.
     */
    public function deleteInvoice(
        string $invoiceId,
        string $residentId
    ): bool {
        $invoice = $this->getInvoiceById($invoiceId, $residentId);

        $file = $invoice->file;

        $deleted = DB::transaction(function () use ($invoice) {
            $invoice->loadMissing('consumptions.items');

            foreach ($invoice->consumptions as $consumption) {
                $consumption->items()->delete();

                $consumption->delete();
            }

            return $invoice->delete();
        });

        if ($deleted && $file) {
            try {
                $this->files->deleteFile($file);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $deleted;
    }

    /**
     * Deletes all of a resident's invoices and the invoice files left without an invoice
     * (RGPD erasure).
     */
    public function deleteAllForResident(Resident $resident): void
    {
        Invoice::query()
            ->where('resident_id', $resident->id)
            ->delete();

        File::query()
            ->where('user_id', $resident->user_id)
            ->where('category', self::FILE_CATEGORY)
            ->whereDoesntHave('invoice')
            ->get()
            ->each(fn (File $file) => $this->files->deleteFile($file));
    }
    
    /**
     * Deletes all invoices of a property (the consumptions cascade) and their files.
     */
    public function deleteAllForProperty(Property $property): void
    {
        $fileIds = Invoice::query()
            ->where('property_id', $property->id)
            ->whereNotNull('file_id')
            ->pluck('file_id');

        Invoice::query()
            ->where('property_id', $property->id)
            ->delete();

        File::query()
            ->whereIn('id', $fileIds)
            ->get()
            ->each(fn (File $file) => $this->files->deleteFile($file));
    }
}
