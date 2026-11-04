<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UploadInvoiceRequest;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Services\Invoice\InvoiceExtractionService;
use App\Services\Invoice\InvoiceService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        if (! $resident) {
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

        if (! $resident) {
            abort(404, 'Resident profile not found.');
        }

        $invoice = $this->service->createInvoiceWithConsumptions(
            residentId: $resident->id,
            invoiceData: $request->invoiceData(),
            consumptionsData: $request->consumptionsData()
        );

        /*
         * Move the PDF from pending to its permanent location.
         */
        if ($invoice->file_id) {
            $disk = Storage::disk('r2');

            $pendingPath = "pending/{$request->user()->id}/{$invoice->file_id}.pdf";
            $finalPath = "invoices/{$resident->id}/{$invoice->file_id}.pdf";

            if ($disk->exists($pendingPath)) {
                $moved = $disk->move($pendingPath, $finalPath);

                if (! $moved) {
                    Log::error('Could not move invoice PDF from pending.', [
                        'file_id' => $invoice->file_id,
                        'pending_path' => $pendingPath,
                        'final_path' => $finalPath,
                    ]);
                }
            } elseif (! $disk->exists($finalPath)) {
                Log::warning('Invoice PDF was not found in pending or final storage.', [
                    'file_id' => $invoice->file_id,
                    'pending_path' => $pendingPath,
                    'final_path' => $finalPath,
                ]);
            }
        }

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

        if (! $resident) {
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

        if (! $resident) {
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
        InvoiceExtractionService $extraction
    ): JsonResponse {
        $file = $request->file('file');
        $fileId = (string) Str::ulid();

        // Store the uploaded PDF temporarily.
        $file->storeAs(
            "pending/{$request->user()->id}",
            "{$fileId}.pdf",
            'r2'
        );

        // Extract data from the temporary uploaded file.
        $extracted = $extraction->extract($file->getRealPath());

        return response()->json([
            'data' => [
                'file_id' => $fileId,
                'extracted' => $extracted,
            ],
        ], 201);
    }

    /**
     * Generate a temporary URL for the invoice PDF.
     */
    public function file(Request $request, string $invoiceId): JsonResponse
    {
        $resident = $request->user()->resident;

        if (! $resident) {
            abort(404, 'Resident profile not found.');
        }

        $invoice = $this->service->getInvoiceById(
            $invoiceId,
            $resident->id
        );

        abort_if(! $invoice->file_id, 404, 'This invoice has no file.');

        $path = "invoices/{$invoice->resident_id}/{$invoice->file_id}.pdf";

        $disk = Storage::disk('r2');

        if ($disk instanceof \Illuminate\Support\Testing\Fakes\FilesystemFake) {
            $url = "https://fakeserver.test/storage/{$path}";
        } elseif ($disk instanceof FilesystemAdapter) {
            $url = $disk->temporaryUrl(
                $path,
                now()->addMinutes(5)
            );
        } else {
            abort(500, 'The R2 disk does not support temporary URLs.');
        }

        return response()->json([
            'data' => [
                'url' => $url,
            ],
        ]);
    }
}
