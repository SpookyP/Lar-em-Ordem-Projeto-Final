<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UploadInvoiceRequest;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Services\Invoice\InvoiceExtractionService;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\FileStorage\FileStorageService;

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
        $resident = $request->user()->resident;

        if (!$resident) {
            abort(404, 'Resident profile not found.');
        }

        $invoices = $this->service->getInvoicesByResident(
            residentId: $resident->id
        );

        if ($invoices->currentPage() > $invoices->lastPage() && $invoices->lastPage() > 0) {
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

        $invoice = $this->service->getInvoiceById(
            invoiceId: $invoiceId,
            residentId: $resident->id
        );

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

        $this->service->deleteInvoice(
            invoiceId: $invoiceId,
            residentId: $resident->id
        );

        return response()->json([
            'message' => 'Invoice was successfully deleted',
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
        $uploaded = $request->file('file');

        // stored like every other user file (a row in `files`), so it is served and deleted the same way
        $file = $files->uploadUserFile($request->user(), $uploaded, InvoiceService::FILE_CATEGORY);

        // Extract data from the uploaded file.
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
    public function file(Request $request, string $invoiceId, FileStorageService $files): JsonResponse
    {
        $resident = $request->user()->resident;

        if (!$resident) {
            abort(404, 'Resident profile not found.');
        }

        $invoice = $this->service->getInvoiceById(
            $invoiceId,
            $resident->id
        );

        abort_if(!$invoice->file, 404, 'This invoice has no file.');

        return response()->json([
            'data' => [
                'url' => $files->getSecureUrl($invoice->file, 5),
            ],
        ]);
    }
}
