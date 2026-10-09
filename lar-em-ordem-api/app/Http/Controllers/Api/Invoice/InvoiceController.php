<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}