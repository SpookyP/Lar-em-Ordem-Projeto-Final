<?php

namespace App\Jobs;

use App\Services\Vault\DocumentExpiryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDocumentExpiry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * handle() does the job's work. Laravel runs it when the job is dispatched
     * (e.g. by the daily scheduler).
     *
     * Laravel injects DocumentExpiryService automatically, ready to use.
     */
    public function handle(DocumentExpiryService $expiryService): void
    {
        $expiryService->checkAllDocuments();
    }
}
