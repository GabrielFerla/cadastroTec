<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Services\CompraService;
use App\Services\VendaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_venda_baixa_estoque_e_calcula_lucro(): void
    {
        // estoque 80 @ custo 21.875 (cenário da seção 4 do plano)
        $produto = Produto::factory()->create(['estoque' => 80, 'custo_medio' => 21.875]);

        $response = $this->postJson('/api/vendas', [
            'cliente' => 'Fulano',
            'produtos' => [['id' => $produto->id, 'quantidade' => 2, 'preco_unitario' => 50]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'concluida')
            ->assertJsonPath('data.total', fn ($v) => (float) $v === 100.0)
            ->assertJsonPath('data.lucro', fn ($v) => (float) $v === 56.25)        // (50 - 21.875) * 2
            ->assertJsonPath('data.itens.0.custo_unitario', fn ($v) => (float) $v === 21.875);

        $this->assertSame(78, $produto->fresh()->estoque);
    }

    public function test_venda_sem_estoque_retorna_422_e_faz_rollback(): void
    {
        $produto = Produto::factory()->create(['estoque' => 1, 'custo_medio' => 10]);

        $this->postJson('/api/vendas', [
            'cliente' => 'Fulano',
            'produtos' => [['id' => $produto->id, 'quantidade' => 2, 'preco_unitario' => 50]],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains((string) $m, 'Estoque insuficiente'));

        // rollback: estoque intacto e nenhuma venda persistida
        $this->assertSame(1, $produto->fresh()->estoque);
        $this->assertDatabaseCount('vendas', 0);
        $this->assertDatabaseCount('venda_itens', 0);
    }

    public function test_cancelar_venda_devolve_estoque_sem_alterar_custo_medio(): void
    {
        $produto = Produto::factory()->create(['estoque' => 0, 'custo_medio' => 0]);

        // compra 10 @ 20 -> estoque 10, custo 20
        app(CompraService::class)->registrar([
            'fornecedor' => 'X',
            'produtos' => [['id' => $produto->id, 'quantidade' => 10, 'preco_unitario' => 20]],
        ]);

        $venda = app(VendaService::class)->registrar([
            'cliente' => 'Fulano',
            'produtos' => [['id' => $produto->id, 'quantidade' => 5, 'preco_unitario' => 30]],
        ]);

        $this->assertSame(5, $produto->fresh()->estoque);

        $this->postJson("/api/vendas/{$venda->id}/cancelar")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelada');

        $produto->refresh();
        $this->assertSame(10, $produto->estoque);                          // estoque revertido
        $this->assertEqualsWithDelta(20, (float) $produto->custo_medio, 0.0001); // custo médio intacto

        // recancelar é bloqueado
        $this->postJson("/api/vendas/{$venda->id}/cancelar")->assertStatus(422);
    }

    public function test_lucro_arredonda_para_centavos_mesmo_com_dizima(): void
    {
        $produto = Produto::factory()->create(['estoque' => 0, 'custo_medio' => 0]);

        // 1 @ 10 e 2 @ 20 -> custo medio = (10 + 40)/3 = 16.6667
        app(CompraService::class)->registrar([
            'fornecedor' => 'X',
            'produtos' => [['id' => $produto->id, 'quantidade' => 1, 'preco_unitario' => 10]],
        ]);
        app(CompraService::class)->registrar([
            'fornecedor' => 'X',
            'produtos' => [['id' => $produto->id, 'quantidade' => 2, 'preco_unitario' => 20]],
        ]);

        // vende 1 @ 30 -> lucro = (30 - 16.6667) = 13.3333 -> arredonda 13.33
        $this->postJson('/api/vendas', [
            'cliente' => 'Fulano',
            'produtos' => [['id' => $produto->id, 'quantidade' => 1, 'preco_unitario' => 30]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.lucro', fn ($v) => (float) $v === 13.33)
            ->assertJsonPath('data.total', fn ($v) => (float) $v === 30.0);
    }

    public function test_venda_com_produto_repetido_no_payload_e_rejeitada(): void
    {
        $produto = Produto::factory()->create(['estoque' => 100, 'custo_medio' => 5]);

        $this->postJson('/api/vendas', [
            'cliente' => 'Fulano',
            'produtos' => [
                ['id' => $produto->id, 'quantidade' => 1, 'preco_unitario' => 10],
                ['id' => $produto->id, 'quantidade' => 1, 'preco_unitario' => 10],
            ],
        ])->assertStatus(422);
    }
}
