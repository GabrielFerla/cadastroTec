<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompraRequest;
use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Services\CompraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompraController extends Controller
{
    public function __construct(private readonly CompraService $compras) {}

    public function index(): AnonymousResourceCollection
    {
        return CompraResource::collection(
            Compra::with('itens.produto')->latest()->get()
        );
    }

    public function store(StoreCompraRequest $request): JsonResponse
    {
        $compra = $this->compras->registrar($request->validated());

        return (new CompraResource($compra))->response()->setStatusCode(201);
    }
}
