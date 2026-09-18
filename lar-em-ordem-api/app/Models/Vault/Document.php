<?php

namespace App\Models\Vault;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $table = 'vault_documents';

    protected $fillable = [
        'housing_id',
        'file_name',
        'file_path',
        'extracted_data',
        'expiration_date'
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'expiration_date' => 'date',
    ];

    public function housing(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Housing\Housing::class);
    }
}
