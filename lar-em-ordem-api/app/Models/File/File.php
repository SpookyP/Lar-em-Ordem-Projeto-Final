<?php

namespace App\Models\File;

use App\Models\Invoice\Invoice;
use App\Models\Vault\Document;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class File extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'title',
        'original_name',
        'path',
        'mime_type',
        'size_in_bytes',
        'category',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function vaultDocument()
    {
        return $this->hasOne(Document::class);
    }
}