<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UploadInvoiceRequest;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Models\Invoice\Invoice;
use App\Services\FileStorage\FileStorageService;
use App\Services\Invoice\InvoiceExtractionService;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $service)
    {
    }

    /**
     * Apresentar a lista de faturas.
     */
    public function index(Request $request)
    {
        // Validar os filtros recebidos.
        $validated = $request->validate([
            'property_id' => ['nullable', 'ulid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'property_id.ulid' => 'O identificador da habitação é inválido.',
        ]);

        // Apenas residentes podem aceder às faturas (ver InvoicePolicy).
        Gate::authorize('viewAny', Invoice::class);

        $resident = $request->user()->resident;

        // Obter as faturas com os filtros aplicados.
        $invoices = $this->service->getInvoicesByResident(
            residentId: $resident->id,
            perPage: $validated['per_page'] ?? 15,
            propertyId: $validated['property_id'] ?? null
        );

        return InvoiceResource::collection($invoices);
    }

    /**
     * Registar uma nova fatura.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        // Apenas residentes podem registar faturas (ver InvoicePolicy).
        Gate::authorize('create', Invoice::class);

        $resident = $request->user()->resident;

        // Criar a fatura e os respetivos consumos.
        $invoice = $this->service->createInvoiceWithConsumptions(
            residentId: $resident->id,
            invoiceData: $request->invoiceData(),
            consumptionsData: $request->consumptionsData()
        );

        return InvoiceResource::make($invoice)
            ->additional([
                'message' => 'Fatura e consumos registados com sucesso!',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Apresentar os detalhes de uma fatura.
     */
    public function show(Request $request, string $invoiceId): JsonResponse
    {
        // Apenas residentes podem aceder às faturas (ver InvoicePolicy).
        Gate::authorize('viewAny', Invoice::class);

        $resident = $request->user()->resident;

        // Obter a fatura pertencente ao residente.
        $invoice = $this->service->getInvoiceById(
            invoiceId: $invoiceId,
            residentId: $resident->id
        );

        Gate::authorize('view', $invoice);

        return InvoiceResource::make($invoice)->response();
    }

    /**
     * Eliminar uma fatura.
     */
    public function destroy(Request $request, string $invoiceId): JsonResponse
    {
        // Apenas residentes podem aceder às faturas (ver InvoicePolicy).
        Gate::authorize('viewAny', Invoice::class);

        $resident = $request->user()->resident;

        // Obter e autorizar a fatura antes da eliminação.
        $invoice = $this->service->getInvoiceById(
            invoiceId: $invoiceId,
            residentId: $resident->id
        );

        Gate::authorize('delete', $invoice);

        $this->service->deleteInvoice(
            invoiceId: $invoiceId,
            residentId: $resident->id
        );

        return response()->json([
            'message' => 'Fatura eliminada com sucesso.',
        ], 200);
    }

    /**
     * Carregar um ficheiro e extrair os dados da fatura.
     */
    public function upload(
        UploadInvoiceRequest $request,
        InvoiceExtractionService $extraction,
        FileStorageService $files
    ): JsonResponse {
        Gate::authorize('create', Invoice::class);

        // Guardar o ficheiro através do serviço centralizado.
        $uploaded = $request->file('file');

        $file = $files->uploadUserFile(
            $request->user(),
            $uploaded,
            InvoiceService::FILE_CATEGORY
        );

        // Extrair os dados do ficheiro carregado.
        $extracted = $extraction->extract($uploaded->getRealPath());

        return response()->json([
            'data' => [
                'file_id' => $file->id,
                'extracted' => $extracted,
            ],
        ], 201);
    }

    /**
     * Gerar um endereço temporário para aceder ao PDF da fatura.
     */
    public function file(
        Request $request,
        string $invoiceId,
        FileStorageService $files
    ): JsonResponse {
        // Apenas residentes podem aceder às faturas (ver InvoicePolicy).
        Gate::authorize('viewAny', Invoice::class);

        $resident = $request->user()->resident;

        // Obter e autorizar a fatura.
        $invoice = $this->service->getInvoiceById(
            $invoiceId,
            $resident->id
        );

        Gate::authorize('view', $invoice);

        // Confirmar que a fatura tem um ficheiro associado.
        abort_if(
            !$invoice->file,
            404,
            'Esta fatura não tem um ficheiro associado.'
        );

        // Gerar um endereço temporário válido durante 5 minutos.
        return response()->json([
            'data' => [
                'url' => $files->getSecureUrl($invoice->file, 5),
            ],
        ]);
    }
}