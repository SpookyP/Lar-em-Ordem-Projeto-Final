<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Privacy\ConsentService;
use App\Services\Privacy\DataExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in user's privacy rights: changing the data sharing answer (withdrawing consent must be as easy
 * as giving it, RGPD art. 7(3)) and downloading a copy of their data (art. 15 and 20).
 * The current answers come with GET /api/user, in "privacy".
 */
class PrivacyController extends Controller
{
    public function __construct(private ConsentService $consents) {}

    public function update(Request $request): JsonResponse
    {
        $granted = $request->validate(
            ['data_sharing' => ['required', 'boolean']],
            [
                'data_sharing.required' => 'Indique se quer partilhar os seus dados.',
                'data_sharing.boolean' => 'Resposta inválida à partilha de dados.',
            ],
        )['data_sharing'];

        $this->consents->set($request->user(), ConsentService::DATA_SHARING, (bool) $granted, 'settings');

        return response()->json($this->consents->summary($request->user()));
    }
    
    public function export(Request $request, DataExportService $export): JsonResponse
    {
        $filename = 'lar-em-ordem-dados-' . now()->toDateString() . '.json';

        return response()->json($export->export($request->user()), 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            // personal data: never kept by the browser or by a proxy
            'Cache-Control' => 'no-store',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}