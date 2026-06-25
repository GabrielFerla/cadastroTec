<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Produto
 */
class ProdutoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'custo_medio' => (float) $this->custo_medio,
            'preco_venda' => (float) $this->preco_venda,
            'estoque' => (int) $this->estoque,
        ];
    }
}
