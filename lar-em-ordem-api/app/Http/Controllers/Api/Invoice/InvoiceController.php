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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'property_id' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $resident = $request->user()->resident;

        if (!$resident) {
            abort(404, 'Resident profile not found.');
        }

        Gate::authorize('viewAny', Invoice::class);

        $invoices = $this->service->getInvoicesByResident(
            residentId: $resident->id,
            perPage: $validated['per_page'] ?? 15,
            propertyId: $validated['property_id'] ?? null
        );

        if (
            $invoices->currentPage() > $invoices->lastPage()
            && $invoices->lastPage() > 0
        ) {
            abort(404, 'Page out of bounds.');
        }

        return InvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $resident = $request->user()->resident;

        if (!$resident) {
            abort(404, 'Resident profile not found.');
        }

        Gate::authorize('create', Invoice::class);

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
     * Display the specified resource.
     */
    public function show(Request $request, string $invoiceId): JsonResponse
    {
        $resident = $request->user()->resident;

        if (!$resident) {
            abort(404, 'Resident profile not found.');
        }

        Gate::authorize('viewAny', Invoice::class);

        $invoice = $this->service->getInvoiceById(
            invoiceId: $invoiceId,
            residentId: $resident->id
        );

        Gate::authorize('view', $invoice);

        return InvoiceResource::make($invoice)->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $invoiceId): JsonResponse
    {
        $resident = $request->user()->resident;

        if (!$resident) {
            abort(404, 'Resident profile not found.');
        }

        Gate::authorize('viewAny', Invoice::class);

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
     * Upload and extract invoice data.
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
     * Generate a temporary URL for the invoice PDF.
     */
    public function file(
        Request $request,
        string $invoiceId,
        FileStorageService $files
    ): JsonResponse {
        $resident = $request->user()->resident;

        if (!$resident) {
            abort(404, 'Resident profile not found.');
        }

        Gate::authorize('viewAny', Invoice::class);

        $invoice = $this->service->getInvoiceById(
            $invoiceId,
            $resident->id
        );

        Gate::authorize('view', $invoice);

        abort_if(
            !$invoice->file,
            404,
            'Esta fatura não tem um ficheiro associado.'
        );

        return response()->json([
            'data' => [
                'url' => $files->getSecureUrl($invoice->file, 5),
            ],
        ]);
    }
}
