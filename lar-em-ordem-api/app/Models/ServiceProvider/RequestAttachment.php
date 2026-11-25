<?php

namespace App\Models\ServiceProvider;

use App\Models\File\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class RequestAttachment extends Model
{
   /** @use HasFactory<\Database\Factories\ServiceProvider\RequestAttachmentFactory> */
    use HasFactory, HasUlids;

    public function assistanceRequest()
    {
        return $this->belongsTo(AssistanceRequest::class);
    }

    /**
     * File stored in R2 (name, path, type and size).
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }

    protected $fillable = [
        'assistance_request_id',
        'file_id',
    ];
}
