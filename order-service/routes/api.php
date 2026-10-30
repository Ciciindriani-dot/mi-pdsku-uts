<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;


Route::get('/health', [OrderController::class, 'health']);

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show'])->where('id', '[0-9]+');
Route::get('/orders/user/{userId}', [OrderController::class, 'getByUser'])->where('userId', '[0-9]+');
Route::patch('/orders/{id}/pay', [OrderController::class, 'pay'])->where('id', '[0-9]+');