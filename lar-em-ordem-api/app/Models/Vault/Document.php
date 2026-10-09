<?php

namespace App\Models\Vault;

use App\Models\Property\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $table = 'vault_documents';

    protected $fillable = [
        'property_id',
        'document_category_id',
        'name',
        'description',
        'file_id',
        'issue_date',
        'expiration_date',
        'extracted_data'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiration_date' => 'date',
        'extracted_data' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Vault\DocumentCategory::class, 'document_category_id');
    }
}
