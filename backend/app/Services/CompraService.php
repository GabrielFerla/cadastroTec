<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;

class CompraService
{
    /**
     * Registra uma compra: entrada de estoque + atualização do custo médio ponderado.
     *
     * @param  array{fornecedor: string, produtos: array<int, array{id: int, quantidade: int, preco_unitario: float|int}>}  $data
     */
    public function registrar(array $data): Compra
    {
        return DB::transaction(function () use ($data) {
            $compra = Compra::create(['fornecedor' => $data['fornecedor']]);
            $total = 0.0;

            foreach ($data['produtos'] as $item) {
                $produto = Produto::lockForUpdate()->findOrFail($item['id']);
                $qtd = (int) $item['quantidade'];
                $preco = (float) $item['preco_unitario'];

                $estoqueAtual = (int) $produto->estoque;
                $custoAtual = (float) $produto->custo_medio;
                $novoEstoque = $estoqueAtual + $qtd;

                // Custo médio ponderado móvel. Quando estoque é 0, reseta para o preço da compra.
                $novoCusto = (($estoqueAtual * $custoAtual) + ($qtd * $preco)) / $novoEstoque;

                $produto->update([
                    'estoque' => $novoEstoque,
                    'custo_medio' => round($novoCusto, 4),
                ]);

                $compra->itens()->create([
                    'produto_id' => $produto->id,
                    'quantidade' => $qtd,
                    'preco_unitario' => $preco,
                ]);

                $total += $qtd * $preco;
            }

            $compra->update(['total' => round($total, 2)]);

            return $compra->load('itens.produto');
        });
    }
}
