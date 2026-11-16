<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'services' => [
                'database' => $this->works(fn () => DB::select('select 1')),
                'storage'  => $this->works(fn () => Storage::disk()->exists('health-check')),
            ],
        ]);
    }

    private function works(callable $check): bool
    {
        try {
            $check();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}