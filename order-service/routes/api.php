<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::patch('/orders/{id}/pay', [OrderController::class, 'pay']);
Route::get('/orders/user/{userId}', [OrderController::class, 'getByUser']);
Route::get('/health', [OrderController::class, 'health']);