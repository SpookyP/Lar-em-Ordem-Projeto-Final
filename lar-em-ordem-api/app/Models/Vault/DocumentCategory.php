<?php

namespace App\Models\Vault;

use Database\Factories\Vault\DocumentCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(DocumentCategoryFactory::class)]
class DocumentCategory extends Model
{
    use HasFactory;

    protected $table = 'document_categories';

    protected $fillable = [
        'name',
        'description',
    ];
}
