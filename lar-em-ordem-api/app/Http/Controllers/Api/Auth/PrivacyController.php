<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Privacy\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in user's consents: what they answered, and changing the data sharing answer
 * (withdrawing consent must be as easy as giving it, RGPD art. 7(3)).
 */
class PrivacyController extends Controller
{
    public function __construct(private ConsentService $consents) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'policy_version' => config('privacy.policy_version'),
            'consents' => $this->consents->current($request->user()),
        ]);
    }

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

        return $this->show($request);
    }
}