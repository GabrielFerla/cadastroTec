<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Compra
 */
class CompraResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fornecedor' => $this->fornecedor,
            'total' => (float) $this->total,
            'created_at' => $this->created_at,
            'itens' => $this->whenLoaded('itens', fn () => $this->itens->map(fn ($item) => [
                'produto_id' => $item->produto_id,
                'nome' => $item->produto?->nome,
                'quantidade' => (int) $item->quantidade,
                'preco_unitario' => (float) $item->preco_unitario,
            ])),
        ];
    }
}
