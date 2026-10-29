<?php

namespace App\Models\Vault;

use App\Models\Property\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Document extends Model
{
    use HasUlids;

    protected $table = 'vault_documents';

    protected $fillable = [
        'property_id',
        'document_category_id',
        'name',
        'description',
        'file_id',
        'issue_date',
        'expiration_date',
        'extracted_data',
        'ficheiro_path',
    ];

    protected $casts = [
        'issue_date' => 'date:Y-m-d',
        'expiration_date' => 'date:Y-m-d',
        'extracted_data' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vault\DocumentCategory::class, 'document_category_id');
    }
}
