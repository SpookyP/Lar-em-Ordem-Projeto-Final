<?php

namespace App\Http\Controllers\Api\Vault;

use App\Http\Controllers\Controller;
use App\Models\Vault\Document;
use App\Services\Vault\PdfExtractionService;
use App\Http\Requests\Vault\StoreDocumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Construtor do controlador. Injeta o serviço responsável pela extração
     * automática de informações dos ficheiros PDF.
     */
    public function __construct(
        private readonly PdfExtractionService $pdfService
    ) {}

    /**
     * Lista todos os documentos de uma habitação.
     */
    public function index(Request $request)
    {
        $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id']
        ]);

        $documents = Document::where('property_id', $request->property_id)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        return \App\Http\Resources\Vault\DocumentResource::collection($documents);
    }

    /**
     * Faz o download do ficheiro PDF.
     */
    public function download(Document $document)
    {
        // O ficheiro é agora procurado pelo file_id gerado (UUID)
        $path = 'vault_documents/' . $document->file_id;

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Ficheiro não encontrado no servidor.'], 404);
        }

        // Faz o download servindo o nome original que o utilizador enviou
        return Storage::disk('local')->download($path, $document->name);
    }

    /**
     * Apaga um documento e o respetivo ficheiro físico.
     */
    public function destroy(Document $document)
    {
        // Apagar o ficheiro físico do disco usando o file_id
        $path = 'vault_documents/' . $document->file_id;

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        // Apagar o registo da base de dados
        $document->delete();

        return response()->json(['message' => 'Documento eliminado com sucesso.']);
    }

    /**
     * Processa o upload de um novo documento, armazena-o de forma segura,
     * executa a extração inteligente de dados (via serviço de PDF) e
     * guarda o registo final na base de dados.
     *
     * @param StoreDocumentRequest $request Pedido validado com os dados do formulário e ficheiro.
     * @return JsonResponse Resposta JSON com o sucesso da operação e o documento criado (Código 201).
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');

        // Gerar um UUID para o ficheiro
        $fileId = (string) Str::uuid();

        // Guardar o ficheiro na pasta com o nome correspondente ao UUID
        $file->storeAs('vault_documents', $fileId, 'local');

        // Obter o caminho absoluto e real do disco para o Python conseguir ler
        $absolutePath = Storage::disk('local')->path('vault_documents/' . $fileId);

        // Extração ocorre na camada Service
        $extractedData = $this->pdfService->extractInfo($absolutePath);

        $document = Document::create([
            'property_id' => $request->validated('property_id'),
            'document_category_id' => $request->validated('document_category_id'),
            'name' => $file->getClientOriginalName(),
            'description' => $request->validated('description'),
            'file_id' => $fileId,
            'issue_date' => $extractedData['issue_date'] ?? null,
            'expiration_date' => $extractedData['expiration_date'] ?? null,
            'extracted_data' => $extractedData,
        ]);

        return response()->json([
            'message' => 'Document archived and parsed successfully.',
            'data' => $document
        ], 201);
    }
}
