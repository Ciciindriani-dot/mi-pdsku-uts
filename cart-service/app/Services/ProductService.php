<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductService
{
    public function getProduct(int $productId): ?array
    {
        try {
            $baseUrl = env('PRODUCT_SERVICE_URL', 'http://product-service:3000');
            $response = Http::get("{$baseUrl}/products/{$productId}");

            if ($response->successful()) {
                $json = $response->json();
                return $json['data'] ?? $json;
            }
            
            return null;
        } catch (Throwable $e) {
            Log::error("Gagal mengambil data produk: ", [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}