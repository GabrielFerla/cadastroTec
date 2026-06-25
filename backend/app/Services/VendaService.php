<?php

namespace App\Services;

use App\Exceptions\EstoqueInsuficienteException;
use App\Models\Produto;
use App\Models\Venda;
use Illuminate\Support\Facades\DB;

class VendaService
{
    /**
     * Registra uma venda: valida estoque de todos os itens, baixa estoque e calcula o lucro.
     *
     * @param  array{cliente: string, produtos: array<int, array{id: int, quantidade: int, preco_unitario: float|int}>}  $data
     *
     * @throws EstoqueInsuficienteException
     */
    public function registrar(array $data): Venda
    {
        return DB::transaction(function () use ($data) {
            // 1) Trava e valida o estoque de TODOS os itens antes de baixar qualquer um.
            $produtos = [];
            foreach ($data['produtos'] as $item) {
                $produto = Produto::lockForUpdate()->findOrFail($item['id']);

                if ((int) $produto->estoque < (int) $item['quantidade']) {
                    throw new EstoqueInsuficienteException($produto, (int) $item['quantidade']);
                }

                $produtos[$produto->id] = $produto;
            }

            $venda = Venda::create([
                'cliente' => $data['cliente'],
                'status' => 'concluida',
            ]);
            $total = 0.0;
            $lucro = 0.0;

            // 2) Baixa estoque, grava o snapshot do custo e calcula o lucro.
            foreach ($data['produtos'] as $item) {
                $produto = $produtos[$item['id']];
                $qtd = (int) $item['quantidade'];
                $preco = (float) $item['preco_unitario'];
                $custoUnit = (float) $produto->custo_medio; // snapshot do custo médio no momento da venda

                $produto->decrement('estoque', $qtd);

                $venda->itens()->create([
                    'produto_id' => $produto->id,
                    'quantidade' => $qtd,
                    'preco_unitario' => $preco,
                    'custo_unitario' => $custoUnit,
                ]);

                $total += $qtd * $preco;
                $lucro += ($preco - $custoUnit) * $qtd;
            }

            $venda->update([
                'total' => round($total, 2),
                'lucro' => round($lucro, 2),
            ]);

            return $venda->load('itens.produto');
        });
    }

    /**
     * Cancela uma venda concluída, revertendo o estoque.
     * Não reconstitui o custo médio (a venda nunca o alterou).
     */
    public function cancelar(Venda $venda): Venda
    {
        return DB::transaction(function () use ($venda) {
            abort_unless(
                $venda->status === 'concluida',
                422,
                'Apenas vendas concluídas podem ser canceladas.'
            );

            foreach ($venda->itens as $item) {
                Produto::lockForUpdate()
                    ->findOrFail($item->produto_id)
                    ->increment('estoque', $item->quantidade);
            }

            $venda->update(['status' => 'cancelada']);

            return $venda->load('itens.produto');
        });
    }
}
