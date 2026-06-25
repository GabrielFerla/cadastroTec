<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Produto;
use App\Models\Venda;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Resumo da "Visão geral": KPIs de estoque/faturamento/lucro,
     * alertas de estoque baixo e atividade recente (compras + vendas).
     *
     * @return array<string, mixed>
     */
    public function resumo(int $estoqueMinimo = 10): array
    {
        // Estoque
        $valorEstoque = (float) Produto::sum(DB::raw('estoque * custo_medio'));
        $unidades = (int) Produto::sum('estoque');

        // Faturamento e lucro (somente vendas concluídas)
        $faturamento = (float) Venda::where('status', 'concluida')->sum('total');
        $lucro = (float) Venda::where('status', 'concluida')->sum('lucro');
        $vendasConcluidas = Venda::where('status', 'concluida')->count();
        $margem = $faturamento > 0 ? round($lucro / $faturamento * 100, 1) : 0.0;

        // Estoque baixo (<= mínimo). estoque 0 => "sem_estoque", senão "baixo".
        $estoqueBaixo = Produto::where('estoque', '<=', $estoqueMinimo)
            ->orderBy('estoque')
            ->orderBy('nome')
            ->get()
            ->map(fn (Produto $p) => [
                'id' => $p->id,
                'nome' => $p->nome,
                'estoque' => (int) $p->estoque,
                'status' => (int) $p->estoque <= 0 ? 'sem_estoque' : 'baixo',
            ]);

        return [
            'valor_em_estoque' => round($valorEstoque, 2),
            'unidades_em_estoque' => $unidades,
            'faturamento' => round($faturamento, 2),
            'lucro' => round($lucro, 2),
            'margem_media' => $margem,
            'vendas_concluidas' => $vendasConcluidas,
            'alertas_estoque' => $estoqueBaixo->count(),
            'estoque_baixo' => $estoqueBaixo->values(),
            'atividade_recente' => $this->atividadeRecente(),
        ];
    }

    /**
     * Últimas movimentações (compras e vendas) unificadas e ordenadas por data desc.
     * Venda entra com valor positivo; compra com valor negativo (saída de caixa).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function atividadeRecente(int $limite = 8)
    {
        $compras = Compra::latest()->take($limite)->get()->map(fn (Compra $c) => [
            'tipo' => 'compra',
            'descricao' => $c->fornecedor,
            'valor' => -1 * round((float) $c->total, 2),
            'status' => null,
            'data' => $c->created_at,
        ]);

        $vendas = Venda::latest()->take($limite)->get()->map(fn (Venda $v) => [
            'tipo' => 'venda',
            'descricao' => $v->cliente,
            'valor' => round((float) $v->total, 2),
            'status' => $v->status,
            'data' => $v->created_at,
        ]);

        return $compras->concat($vendas)
            ->sortByDesc('data')
            ->take($limite)
            ->values();
    }
}
