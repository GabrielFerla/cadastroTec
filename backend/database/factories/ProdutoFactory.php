<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Produto>
 */
class ProdutoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->words(2, true),
            'preco_venda' => fake()->randomFloat(2, 10, 500),
            'custo_medio' => 0,
            'estoque' => 0,
        ];
    }
}
