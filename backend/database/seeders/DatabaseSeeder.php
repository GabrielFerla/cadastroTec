<?php

namespace Database\Seeders;

use App\Models\Produto;
use App\Models\Venda;
use App\Services\CompraService;
use App\Services\VendaService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Popula o banco com dados de demonstração coerentes.
     *
     * Usa os Services de propósito: assim estoque, custo médio ponderado e lucro
     * são calculados exatamente como em produção (nada é "chutado").
     */
    public function run(): void
    {
        $compras = app(CompraService::class);
        $vendas = app(VendaService::class);

        // 1) Produtos (estoque e custo médio começam em 0).
        $catalogo = [
            'Notebook Dell' => 3500,
            'Mouse Logitech' => 80,
            'Teclado Mecânico' => 250,
            'Monitor 24"' => 1200,
            'Cadeira Gamer' => 900,
            'Marca-Texto Amarelo' => 12,
            'Grampeador 26/6' => 45,
        ];

        $id = [];
        foreach ($catalogo as $nome => $preco) {
            $id[$nome] = Produto::create([
                'nome' => $nome,
                'preco_venda' => $preco,
                'custo_medio' => 0,
                'estoque' => 0,
            ])->id;
        }

        // 2) Compras — entrada de estoque + custo médio.
        $compras->registrar([
            'fornecedor' => 'Tech Distribuidora',
            'produtos' => [
                ['id' => $id['Notebook Dell'], 'quantidade' => 10, 'preco_unitario' => 2500],
                ['id' => $id['Mouse Logitech'], 'quantidade' => 100, 'preco_unitario' => 30],
                ['id' => $id['Teclado Mecânico'], 'quantidade' => 50, 'preco_unitario' => 120],
                ['id' => $id['Monitor 24"'], 'quantidade' => 20, 'preco_unitario' => 800],
                ['id' => $id['Cadeira Gamer'], 'quantidade' => 15, 'preco_unitario' => 500],
                ['id' => $id['Marca-Texto Amarelo'], 'quantidade' => 20, 'preco_unitario' => 5],
                ['id' => $id['Grampeador 26/6'], 'quantidade' => 10, 'preco_unitario' => 22],
            ],
        ]);

        // Segunda compra desloca o custo médio de alguns itens (ex.: Notebook -> 2600).
        $compras->registrar([
            'fornecedor' => 'Info Atacado',
            'produtos' => [
                ['id' => $id['Notebook Dell'], 'quantidade' => 5, 'preco_unitario' => 2800],
                ['id' => $id['Mouse Logitech'], 'quantidade' => 50, 'preco_unitario' => 36],
                ['id' => $id['Monitor 24"'], 'quantidade' => 10, 'preco_unitario' => 860],
            ],
        ]);

        // 3) Vendas — saída de estoque + lucro (snapshot do custo no momento da venda).
        $vendas->registrar([
            'cliente' => 'Maria Silva',
            'produtos' => [
                ['id' => $id['Notebook Dell'], 'quantidade' => 2, 'preco_unitario' => 3500],
                ['id' => $id['Mouse Logitech'], 'quantidade' => 5, 'preco_unitario' => 80],
            ],
        ]);

        $vendas->registrar([
            'cliente' => 'João Souza',
            'produtos' => [
                ['id' => $id['Monitor 24"'], 'quantidade' => 3, 'preco_unitario' => 1200],
                ['id' => $id['Teclado Mecânico'], 'quantidade' => 4, 'preco_unitario' => 250],
            ],
        ]);

        $vendas->registrar([
            'cliente' => 'Ana Costa',
            'produtos' => [
                ['id' => $id['Cadeira Gamer'], 'quantidade' => 2, 'preco_unitario' => 900],
            ],
        ]);

        // Esgota o Marca-Texto (-> sem estoque) e derruba o Grampeador (-> estoque baixo),
        // para a Visão geral exibir os alertas de estoque.
        $vendas->registrar([
            'cliente' => 'Livraria Saber',
            'produtos' => [
                ['id' => $id['Marca-Texto Amarelo'], 'quantidade' => 20, 'preco_unitario' => 12],
                ['id' => $id['Grampeador 26/6'], 'quantidade' => 6, 'preco_unitario' => 45],
            ],
        ]);

        // 4) Uma venda cancelada, para a tela de listagem mostrar os dois status.
        $cancelada = $vendas->registrar([
            'cliente' => 'Pedro Lima',
            'produtos' => [
                ['id' => $id['Notebook Dell'], 'quantidade' => 1, 'preco_unitario' => 3600],
            ],
        ]);
        $vendas->cancelar(Venda::findOrFail($cancelada->id));
    }
}
