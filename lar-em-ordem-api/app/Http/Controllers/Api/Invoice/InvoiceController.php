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
use \Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Criar a fatura e os respetivos consumos (com o PDF, se vier).
        $invoice = $this->service->createInvoiceWithConsumptions(
            user: $request->user(),
            invoiceData: $request->invoiceData(),
            consumptionsData: $request->consumptionsData(),
            pdf: $request->file('file')
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
     * Ler o PDF de uma fatura e devolver os dados extraídos, para preencher o formulário.
     * O ficheiro não é guardado: só vai para o R2 quando a fatura é registada (store).
     */
    public function upload(
        UploadInvoiceRequest $request,
        InvoiceExtractionService $extraction
    ): JsonResponse {
        Gate::authorize('create', Invoice::class);

        $extracted = $extraction->extract($request->file('file')->getRealPath());

        return response()->json([
            'data' => [
                'extracted' => $extracted,
            ],
        ]);
    }

    /**
     * The invoice PDF itself, streamed through the API.
     */
    public function fileContent(
        Request $request,
        string $invoiceId,
        FileStorageService $files
    ): StreamedResponse {
        Gate::authorize('viewAny', Invoice::class);

        $invoice = $this->service->getInvoiceById($invoiceId, $request->user()->resident->id);

        Gate::authorize('view', $invoice);

        abort_if(!$invoice->file, 404, 'Esta fatura não tem um ficheiro associado.');

        return $files->contentResponse($invoice->file);
    }
}