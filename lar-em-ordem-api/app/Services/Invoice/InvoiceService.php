<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Invoice;
use App\Models\File\File;
use App\Models\User\Resident;
use App\Services\FileStorage\FileStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    // Categoria utilizada para identificar os ficheiros das faturas.
    public const FILE_CATEGORY = 'invoices';

    public function __construct(private FileStorageService $files)
    {
    }

    /**
     * Obter as faturas de um residente,
     * opcionalmente filtradas por propriedade.
     */
    public function getInvoicesByResident(
        string $residentId,
        int $perPage = 15,
        ?string $propertyId = null
    ): LengthAwarePaginator {
        $query = Invoice::query()
            ->where('resident_id', $residentId);

        // Aplicar o filtro da propriedade apenas quando é fornecido.
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
     * Obter uma fatura específica pelo ID,
     * garantindo que pertence ao residente.
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
     * Criar uma fatura e os respetivos consumos
     * dentro de uma transação.
     */
    public function createInvoiceWithConsumptions(
        string $residentId,
        array $invoiceData,
        array $consumptionsData
    ): Invoice {
        // O ficheiro já foi guardado e validado.
        // Aqui apenas associamos o seu ID à fatura.
        return DB::transaction(function () use (
            $residentId,
            $invoiceData,
            $consumptionsData
        ) {
            // Associar a fatura ao residente autenticado.
            $invoiceData['resident_id'] = $residentId;

            $invoice = Invoice::create($invoiceData);

            // Criar os consumos associados à fatura.
            if (!empty($consumptionsData)) {
                foreach ($consumptionsData as $item) {
                    $consumption = $invoice->consumptions()->create([
                        'property_id' => $invoice->property_id,
                        'consumption_type_id' => $item['consumption_type_id'],
                        'period_start' => $invoice->period_start,
                        'period_end' => $invoice->period_end,
                        'amount' => $item['amount'],
                        'cost' => $item['cost'],
                    ]);

                    // Criar os itens individuais de cada consumo.
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

            // Devolver a fatura com as respetivas relações carregadas.
            return $invoice->load([
                'property',
                'file',
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
        // Confirmar que a fatura pertence ao residente.
        $invoice = $this->getInvoiceById($invoiceId, $residentId);

        // Guardar a referência ao ficheiro antes de eliminar a fatura.
        $file = $invoice->file;

        // Eliminar os registos dentro de uma única transação.
        $deleted = DB::transaction(function () use ($invoice) {
            // Garantir que os consumos e respetivos itens estão carregados.
            $invoice->loadMissing('consumptions.items');

            foreach ($invoice->consumptions as $consumption) {
                // Apagar primeiro os itens associados ao consumo.
                $consumption->items()->delete();

                // Apagar depois o próprio consumo.
                $consumption->delete();
            }

            // Por fim, apagar a fatura.
            return $invoice->delete();
        });

        // Depois de apagar a fatura, eliminar o ficheiro associado.
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
     * Apagar todas as faturas de um residente
     * e os ficheiros de faturas que já não estejam associados.
     */
    public function deleteAllForResident(Resident $resident): void
    {
        // Apagar todas as faturas do residente.
        Invoice::query()
            ->where('resident_id', $resident->id)
            ->delete();

        // Apagar os ficheiros da categoria invoices
        // que já não estejam associados a nenhuma fatura.
        File::query()
            ->where('user_id', $resident->user_id)
            ->where('category', self::FILE_CATEGORY)
            ->whereDoesntHave('invoice')
            ->get()
            ->each(fn (File $file) => $this->files->deleteFile($file));
    }
}
