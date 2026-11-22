<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerPageRequest;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $service){}

    public function index(PerPageRequest $request)
    {
        $validated = $request->validate([
            'property_id' => ['nullable', 'ulid'],
        ], [
            'property_id.ulid' => 'O identificador da habitação é inválido.',
        ]);

        Gate::authorize('viewAny', Invoice::class);

        $resident = $request->user()->resident;

        $invoices = $this->service->getInvoicesByResident(
            residentId: $resident->id,
            perPage: $request->validated('per_page') ?? 5,
            propertyId: $validated['property_id'] ?? null
        );

        return InvoiceResource::collection($invoices);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        Gate::authorize('create', Invoice::class);

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

    public function show(Request $request, string $invoiceId): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        $resident = $request->user()->resident;

        $invoice = $this->service->getInvoiceById(
            invoiceId: $invoiceId,
            residentId: $resident->id
        );

        Gate::authorize('view', $invoice);

        return InvoiceResource::make($invoice)->response();
    }

    public function destroy(Request $request, string $invoiceId): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        $resident = $request->user()->resident;

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
     * Reads an invoice PDF and returns the extracted data, to pre-fill the form.
     * The file is not stored: it only goes to R2 when the invoice is registered (store).
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

        $invoice = $this->service->getInvoiceById(
            $invoiceId,
            $request->user()->resident->id
        );

        Gate::authorize('view', $invoice);

        abort_if(!$invoice->file, 404, 'Esta fatura não tem um ficheiro associado.');

        return $files->contentResponse($invoice->file);
    }
}
