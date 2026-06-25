<?php

namespace Tests\Feature;

use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastra_produto_com_estoque_e_custo_zero(): void
    {
        $response = $this->postJson('/api/produtos', [
            'nome' => 'Caneta Azul',
            'preco_venda' => 5.50,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nome', 'Caneta Azul')
            ->assertJsonPath('data.preco_venda', 5.5)
            ->assertJsonPath('data.custo_medio', 0)
            ->assertJsonPath('data.estoque', 0);

        $this->assertDatabaseHas('produtos', ['nome' => 'Caneta Azul', 'estoque' => 0]);
    }

    public function test_nome_precisa_de_no_minimo_3_caracteres(): void
    {
        $this->postJson('/api/produtos', ['nome' => 'AB', 'preco_venda' => 10])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nome');
    }

    public function test_preco_venda_precisa_ser_positivo(): void
    {
        $this->postJson('/api/produtos', ['nome' => 'Produto', 'preco_venda' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('preco_venda');
    }

    public function test_index_retorna_campos_do_contrato(): void
    {
        Produto::factory()->create(['nome' => 'Item', 'estoque' => 7]);

        $this->getJson('/api/produtos')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'nome', 'custo_medio', 'preco_venda', 'estoque']],
            ]);
    }
}
