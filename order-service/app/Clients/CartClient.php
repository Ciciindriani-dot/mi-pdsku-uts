<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CartClient
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('CART_SERVICE_URL', 'http://localhost:3002');
    }

    public function getCart($userId)
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/carts/{$userId}");

            if ($response->failed()) {
                throw new Exception("Cart service error status: " . $response->status());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error("Gagal menghubungi cart-service: " . $e->getMessage());
            throw new Exception("Cart service tidak dapat dihubungi, coba lagi nanti", 503);
        }
    }

    public function clearCart($userId)
    {
        try {
            $response = Http::timeout(5)->delete("{$this->baseUrl}/carts/{$userId}");
            return $response->json();
        } catch (Exception $e) {
            Log::error("Error saat mengosongkan keranjang: " . $e->getMessage());
            return null;
        }
    }
}