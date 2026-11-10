<?php

namespace App\Http\Controllers\Api\Vault;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vault\DocumentCategoryResource;
use App\Models\Vault\DocumentCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentCategoryController extends Controller
{
    /**
     * Lista todas as categorias de documentos do Cofre Digital.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = DocumentCategory::orderBy('id')->get();

        return DocumentCategoryResource::collection($categories);
    }
}
