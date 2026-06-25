<?php

use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\VendaController;
use Illuminate\Support\Facades\Route;

Route::apiResource('produtos', ProdutoController::class)->only(['index', 'store']);
Route::apiResource('compras', CompraController::class)->only(['index', 'store']);
Route::apiResource('vendas', VendaController::class)->only(['index', 'store']);
Route::post('vendas/{venda}/cancelar', [VendaController::class, 'cancelar']);
