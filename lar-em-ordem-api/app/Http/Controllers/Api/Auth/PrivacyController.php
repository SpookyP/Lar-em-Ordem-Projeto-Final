<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Privacy\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Changing the signed-in user's data sharing answer (withdrawing consent must be as easy as giving it,
 * RGPD art. 7(3)). The current answers come with GET /api/user, in "privacy".
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
}