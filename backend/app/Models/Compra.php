<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    use HasFactory;

    protected $fillable = [
        'fornecedor',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }

    public function itens(): HasMany
    {
        return $this->hasMany(CompraItem::class);
    }
}
