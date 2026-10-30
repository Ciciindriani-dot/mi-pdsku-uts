<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Menampilkan semua daftar order
    public function index()
    {
        $orders = Order::with('items')->get();
        return response()->json([
            'success' => true,
            'data' => $orders
        ], 200);
    }

    // Menyimpan order baru (Transaksi) menggunakan StoreOrderRequest
    public function store(StoreOrderRequest $request)
    {
        try {
            $order = DB::transaction(function () use ($request) {
                // Hitung total amount dari items
                $totalAmount = 0;
                foreach ($request->items as $item) {
                    $totalAmount += $item['unit_price'] * $item['quantity'];
                }

                // Buat data order utama
                $order = Order::create([
                    'user_id' => $request->user_id,
                    'status' => 'pending',
                    'total_amount' => $totalAmount,
                ]);

                // Buat item-item order
                foreach ($request->items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['unit_price'] * $item['quantity'],
                    ]);
                }

                return $order->load('items');
            });

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Menampilkan detail order berdasarkan ID
    public function show($id)
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail order berhasil diambil',
            'data' => $order
        ], 200);
    }
}