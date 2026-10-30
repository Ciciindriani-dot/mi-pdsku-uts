<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductClient
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('PRODUCT_SERVICE_URL', 'http://localhost:3001');
    }

    public function getProduct($productId)
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/products/{$productId}");

            if ($response->status() === 404) {
                return null;
            }

            if ($response->failed()) {
                throw new Exception("Product service error status: " . $response->status());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error("Gagal menghubungi product-service: " . $e->getMessage());
            throw new Exception("Product service tidak dapat dihubungi, coba lagi nanti", 503);
        }
    }
}