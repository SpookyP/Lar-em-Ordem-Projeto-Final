<?php

namespace App\Models\Condominium;

use App\Models\File\File;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CondominiumDocument extends Model
{
    /** @use HasFactory<\Database\Factories\Condominium\CondominiumDocumentFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['condominium_id', 'condominium_document_category_id', 'file_id', 'title', 'description', 'document_date'];

    protected function casts(): array
    {
        return ['document_date' => 'date'];
    }

    public function condominium()
    {
        return $this->belongsTo(Condominium::class);
    }

    public function category()
    {
        return $this->belongsTo(CondominiumDocumentCategory::class, 'condominium_document_category_id');
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}