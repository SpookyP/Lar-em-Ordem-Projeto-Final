<?php

namespace App\Models\ServiceProvider;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestAttachment extends Model
{
    /** @use HasFactory<\Database\Factories\RequestAttachmentFactory> */
    use HasFactory, SoftDeletes;

    public function assistanceRequest()
    {
        return $this->belongsTo(AssistanceRequest::class);
    }
}
