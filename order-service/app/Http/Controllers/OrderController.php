<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
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

    // Menyimpan order baru (Transaksi)
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.product_name' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

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
            'data' => $order
        ], 200);
    }
}