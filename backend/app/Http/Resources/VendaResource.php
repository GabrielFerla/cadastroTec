<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Venda
 */
class VendaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente' => $this->cliente,
            'status' => $this->status,
            'total' => (float) $this->total,
            'lucro' => (float) $this->lucro,
            'created_at' => $this->created_at,
            'itens' => $this->whenLoaded('itens', fn () => $this->itens->map(fn ($item) => [
                'produto_id' => $item->produto_id,
                'nome' => $item->produto?->nome,
                'quantidade' => (int) $item->quantidade,
                'preco_unitario' => (float) $item->preco_unitario,
                'custo_unitario' => (float) $item->custo_unitario,
            ])),
        ];
    }
}
