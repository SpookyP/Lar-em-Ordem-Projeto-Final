<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UploadInvoiceRequest;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Services\Invoice\InvoiceService;
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
        $invoices = $this->service->getInvoicesByUser(
            userId: $request->user()->id
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
        $invoice = $this->service->createInvoiceWithConsumptions(
            userId: $request->user()->id,
            invoiceData: $request->invoiceData(),
            consumptionsData: $request->input('consumptions', [])
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
        $invoice = $this->service->getInvoiceById(
            invoiceId: $invoiceId,
            userId: $request->user()->id
        );

        return InvoiceResource::make($invoice)->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $invoiceId): JsonResponse
    {
        $this->service->deleteInvoice(
            invoiceId: $invoiceId,
            userId: $request->user()->id
        );

        return response()->json([
            'message' => 'Invoice was successfully deleted',
        ], 200);
    }

    public function upload(UploadInvoiceRequest $request): JsonResponse
    {
        $fileId = (string) Str::ulid();

        $request->file('file')->storeAs("pending/{$request->user()->id}", "{$fileId}.pdf", 'r2');

        return response()->json(['data' => ['file_id' => $fileId]], 201);
    }

    public function file(Request $request, string $invoiceId): JsonResponse
    {
        $invoice = $this->service->getInvoiceById($invoiceId, $request->user()->id); // 404 se não for dele

        abort_if(! $invoice->file_id, 404, 'This invoice has no file.');

        $url = Storage::disk('r2')->temporaryUrl(
            "invoices/{$invoice->user_id}/{$invoice->file_id}.pdf",
            now()->addMinutes(5)
        );

        return response()->json(['data' => ['url' => $url]]);
    }
}