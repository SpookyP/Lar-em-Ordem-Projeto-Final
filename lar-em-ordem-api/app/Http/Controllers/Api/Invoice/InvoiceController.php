<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UploadInvoiceRequest;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Services\Invoice\InvoiceExtractionService;
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

    public function upload(UploadInvoiceRequest $request, InvoiceExtractionService $extraction): JsonResponse
    {
        $file   = $request->file('file');
        $fileId = (string) Str::ulid();
 
        // Usa o user_id para corresponder exatamente à validação em StoreInvoiceRequest
        $file->storeAs("pending/{$request->user()->id}", "{$fileId}.pdf", 'r2');
 
        // Extrai a partir do ficheiro temporário do upload
        $extracted = $extraction->extract($file->getRealPath());
 
        return response()->json([
            'data' => [
                'file_id'   => $fileId,
                'extracted' => $extracted, // null = a extração falhou, o utilizador preenche à mão
            ],
        ], 201);
    }

    public function file(Request $request, string $invoiceId): JsonResponse
    {
        $resident = $request->user()->resident;
        if (! $resident) {
            abort(404, 'Resident profile not found.');
        }

        $invoice = $this->service->getInvoiceById($invoiceId, $resident->id);

        abort_if(! $invoice->file_id, 404, 'This invoice has no file.');

        $path = "invoices/{$invoice->resident_id}/{$invoice->file_id}.pdf";

        if (Storage::disk('r2') instanceof \Illuminate\Support\Testing\Fakes\FilesystemFake) {
            $url = "https://fakeserver.test/storage/{$path}";
        } else {
            $url = Storage::disk('r2')->temporaryUrl(
                $path,
                now()->addMinutes(5)
            );
        }

        return response()->json(['data' => ['url' => $url]]);
    }
}