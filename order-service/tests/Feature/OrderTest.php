<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order_with_valid_data(): bool|array|string
    {
        $response = $this->postJson('/api/orders', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => 101,
                    'product_name' => 'Laptop Gaming',
                    'unit_price' => 15000000,
                    'quantity' => 1,
                ]
            ]
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Order berhasil dibuat',
                 ]);
        
        return true;
    }

    public function test_cannot_create_order_with_invalid_data(): void
    {
        $response = $this->postJson('/api/orders', [
            'user_id' => 'invalid-id', // Harus integer
            'items' => [] // Minimal harus ada 1 item
        ]);

        $response->assertStatus(422); // Unprocessable Entity (gagal validasi)
    }
}