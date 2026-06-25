<?php

namespace App\Exceptions;

use App\Models\Produto;
use Exception;
use Illuminate\Http\JsonResponse;

class EstoqueInsuficienteException extends Exception
{
    public function __construct(
        public readonly Produto $produto,
        public readonly int $solicitado,
    ) {
        parent::__construct(
            "Estoque insuficiente para o produto \"{$produto->nome}\". "
            ."Disponível: {$produto->estoque}, solicitado: {$solicitado}."
        );
    }

    /**
     * Renderiza a exception como resposta JSON 422.
     * O Laravel chama render() automaticamente (renderable exception).
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 422);
    }
}
