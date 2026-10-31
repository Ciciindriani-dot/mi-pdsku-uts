<?php

namespace App\Services;

use App\Clients\CartClient;
use App\Clients\ProductClient;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    protected $cartClient;
    protected $productClient;

    public function __construct(CartClient $cartClient, ProductClient $productClient)
    {
        $this->cartClient = $cartClient;
        $this->productClient = $productClient;
    }

    public function createOrder($userId)
    {
        $cartData = $this->cartClient->getCart($userId);
        $items = $cartData['data']['items'] ?? [];

        if (empty($items)) {
            throw new Exception("Keranjang belanja kosong", 409);
        }

        $orderItemsData = [];
        $totalAmount = 0;

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];

            $productRes = $this->productClient->getProduct($productId);
            if (!$productRes || !isset($productRes['data'])) {
                throw new Exception("Produk dengan id {$productId} tidak ditemukan", 409);
            }

            $product = $productRes['data'];
            $stock = $product['stock'];
            $unitPrice = (float) $product['price'];
            $productName = $product['name'];

            if ($stock < $quantity) {
                throw new Exception("Stok {$productName} tidak mencukupi: diminta {$quantity}, tersedia {$stock}", 409);
            }

            $subtotal = $unitPrice * $quantity;
            $totalAmount += $subtotal;

            $orderItemsData[] = [
                'product_id' => $productId,
                'product_name' => $productName,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];
        }

        // Transaksi database agar atomik
        return DB::transaction(function () use ($userId, $totalAmount, $orderItemsData) {
            $order = Order::create([
                'user_id' => $userId,
                'status' => 'PENDING',
                'total_amount' => $totalAmount,
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }
            
            return $order->load('items');
        });

        try {
            $this->cartClient->clearCart($userId);
        } catch (\Exception $e) {
            // Catat kegagalan ke log, tapi pesanan tetap aman (tidak dibatalkan)
            \Illuminate\Support\Facades\Log::warning("Gagal mengosongkan keranjang untuk user {$userId}: " . $e->getMessage());
        }

        return $order->load('items');
    }

    public function getOrderById($id)
    {
        $order = Order::with('items')->find($id);
        if (!$order) {
            throw new Exception("Pesanan dengan id {$id} tidak ditemukan", 404);
        }
        return $order;
    }

    public function getOrdersByUser($userId, $status = null)
    {
        if ($status && !in_array($status, ['PENDING', 'PAID'])) {
            throw new Exception("Status harus PENDING atau PAID");
        }

        $query = Order::with('items')->where('user_id', $userId);
        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function payOrder($id)
    {
        $order = Order::find($id);
        if (!$order) {
            throw new Exception("Pesanan dengan id {$id} tidak ditemukan", 404);
        }

        if ($order->status !== 'PENDING') {
            throw new Exception("Pesanan berstatus {$order->status}, hanya pesanan PENDING yang dapat dibayar", 409);
        }

        $order->update(['status' => 'PAID']);
        return $order->load('items');
    }
}