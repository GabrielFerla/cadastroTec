<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fornecedor' => ['required', 'string', 'max:255'],
            'produtos' => ['required', 'array', 'min:1'],
            'produtos.*.id' => ['required', 'integer', 'distinct', 'exists:produtos,id'],
            'produtos.*.quantidade' => ['required', 'integer', 'min:1'],
            'produtos.*.preco_unitario' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fornecedor.required' => 'O fornecedor é obrigatório.',
            'produtos.required' => 'Informe ao menos um produto.',
            'produtos.min' => 'Informe ao menos um produto.',
            'produtos.*.id.exists' => 'Produto não encontrado.',
            'produtos.*.id.distinct' => 'Há produtos repetidos na compra.',
            'produtos.*.quantidade.min' => 'A quantidade deve ser no mínimo 1.',
            'produtos.*.preco_unitario.gt' => 'O preço unitário deve ser positivo.',
        ];
    }
}
