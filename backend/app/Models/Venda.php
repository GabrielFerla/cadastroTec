<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venda extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente',
        'total',
        'lucro',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'lucro' => 'decimal:2',
        ];
    }

    public function itens(): HasMany
    {
        return $this->hasMany(VendaItem::class);
    }
}
