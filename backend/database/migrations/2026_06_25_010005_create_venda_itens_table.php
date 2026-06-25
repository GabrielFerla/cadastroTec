<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venda_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->constrained()->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained();
            $table->integer('quantidade');
            $table->decimal('preco_unitario', 10, 2); // preço de venda praticado
            $table->decimal('custo_unitario', 15, 4); // snapshot do custo médio na venda
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venda_itens');
    }
};
