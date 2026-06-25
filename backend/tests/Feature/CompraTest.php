<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Services\CompraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompraTest extends TestCase
{
    use RefreshDatabase;

    public function test_compra_atualiza_estoque_e_custo_medio_ponderado(): void
    {
        $produto = Produto::factory()->create(['estoque' => 0, 'custo_medio' => 0]);

        $service = app(CompraService::class);

        // 50 un. @ R$20 -> custo 20.0000
        $service->registrar([
            'fornecedor' => 'Fornecedor X',
            'produtos' => [['id' => $produto->id, 'quantidade' => 50, 'preco_unitario' => 20]],
        ]);

        // +30 un. @ R$25 -> (50*20 + 30*25)/80 = 1750/80 = 21.8750
        $service->registrar([
            'fornecedor' => 'Fornecedor X',
            'produtos' => [['id' => $produto->id, 'quantidade' => 30, 'preco_unitario' => 25]],
        ]);

        $produto->refresh();
        $this->assertSame(80, $produto->estoque);
        $this->assertEqualsWithDelta(21.875, (float) $produto->custo_medio, 0.0001);
    }

    public function test_endpoint_de_compra_registra_total_e_itens(): void
    {
        $produto = Produto::factory()->create(['estoque' => 0, 'custo_medio' => 0]);

        $this->postJson('/api/compras', [
            'fornecedor' => 'ACME',
            'produtos' => [['id' => $produto->id, 'quantidade' => 10, 'preco_unitario' => 3.5]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.fornecedor', 'ACME')
            ->assertJsonPath('data.total', fn ($v) => (float) $v === 35.0)
            ->assertJsonPath('data.itens.0.quantidade', 10);

        $this->assertEqualsWithDelta(3.5, (float) $produto->fresh()->custo_medio, 0.0001);
        $this->assertSame(10, $produto->fresh()->estoque);
    }

    public function test_compra_com_produto_repetido_no_payload_e_rejeitada(): void
    {
        $produto = Produto::factory()->create();

        $this->postJson('/api/compras', [
            'fornecedor' => 'ACME',
            'produtos' => [
                ['id' => $produto->id, 'quantidade' => 5, 'preco_unitario' => 10],
                ['id' => $produto->id, 'quantidade' => 3, 'preco_unitario' => 12],
            ],
        ])->assertStatus(422);
    }
}
