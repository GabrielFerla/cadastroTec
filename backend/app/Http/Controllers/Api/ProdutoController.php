<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdutoRequest;
use App\Http\Resources\ProdutoResource;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProdutoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProdutoResource::collection(Produto::orderBy('nome')->get());
    }

    public function store(StoreProdutoRequest $request): JsonResponse
    {
        $produto = Produto::create([
            'nome' => $request->validated('nome'),
            'preco_venda' => $request->validated('preco_venda'),
            'custo_medio' => 0,
            'estoque' => 0,
        ]);

        return (new ProdutoResource($produto))->response()->setStatusCode(201);
    }
}
