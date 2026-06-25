<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraItem extends Model
{
    use HasFactory;

    protected $table = 'compra_itens';

    protected $fillable = [
        'compra_id',
        'produto_id',
        'quantidade',
        'preco_unitario',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
            'preco_unitario' => 'decimal:2',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
