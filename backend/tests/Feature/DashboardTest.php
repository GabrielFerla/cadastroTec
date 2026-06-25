<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Services\CompraService;
use App\Services\VendaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_resume_kpis_alertas_e_atividade(): void
    {
        $p1 = Produto::factory()->create(['nome' => 'Produto A', 'preco_venda' => 50]);
        $p2 = Produto::factory()->create(['nome' => 'Produto B', 'preco_venda' => 100]);

        // Compra: P1 10@20 (custo 20), P2 5@40 (custo 40)
        app(CompraService::class)->registrar([
            'fornecedor' => 'Fornecedor X',
            'produtos' => [
                ['id' => $p1->id, 'quantidade' => 10, 'preco_unitario' => 20],
                ['id' => $p2->id, 'quantidade' => 5, 'preco_unitario' => 40],
            ],
        ]);

        // Venda: P1 2@50 (lucro (50-20)*2=60), P2 5@100 (lucro (100-40)*5=300) -> esgota P2
        app(VendaService::class)->registrar([
            'cliente' => 'Cliente Y',
            'produtos' => [
                ['id' => $p1->id, 'quantidade' => 2, 'preco_unitario' => 50],
                ['id' => $p2->id, 'quantidade' => 5, 'preco_unitario' => 100],
            ],
        ]);

        // Estado: P1 estoque 8 @ custo 20 = 160 ; P2 estoque 0 @ custo 40 = 0 -> valor 160, 8 un
        // Faturamento = 100 + 500 = 600 ; Lucro = 60 + 300 = 360 ; margem = 60.0%
        $this->getJson('/api/dashboard?estoque_minimo=10')
            ->assertOk()
            ->assertJsonPath('data.valor_em_estoque', fn ($v) => (float) $v === 160.0)
            ->assertJsonPath('data.unidades_em_estoque', 8)
            ->assertJsonPath('data.faturamento', fn ($v) => (float) $v === 600.0)
            ->assertJsonPath('data.lucro', fn ($v) => (float) $v === 360.0)
            ->assertJsonPath('data.margem_media', fn ($v) => (float) $v === 60.0)
            ->assertJsonPath('data.vendas_concluidas', 1)
            // P2 sem estoque (0) e P1 com 8 (<=10) -> 2 alertas
            ->assertJsonPath('data.alertas_estoque', 2)
            ->assertJsonPath('data.estoque_baixo.0.status', 'sem_estoque')
            ->assertJsonPath('data.estoque_baixo.0.nome', 'Produto B')
            ->assertJsonCount(2, 'data.atividade_recente'); // 1 compra + 1 venda
    }

    public function test_dashboard_zera_margem_sem_vendas(): void
    {
        Produto::factory()->create(['estoque' => 5, 'custo_medio' => 10]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.faturamento', fn ($v) => (float) $v === 0.0)
            ->assertJsonPath('data.margem_media', fn ($v) => (float) $v === 0.0)
            ->assertJsonPath('data.vendas_concluidas', 0);
    }
}
