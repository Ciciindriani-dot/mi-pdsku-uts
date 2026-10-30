<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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

    // Simulasi pembayaran pesanan: mengubah status dari PENDING menjadi PAID
    public function pay($id)
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'message' => "Pesanan dengan id {$id} tidak ditemukan"
            ], 404);
        }

        if ($order->status !== 'PENDING') {
            return response()->json([
                'message' => "Pesanan berstatus {$order->status}, hanya pesanan PENDING yang dapat dibayar"
            ], 409);
        }

        $order->status = 'PAID';
        $order->save();

        return response()->json([
            'message' => 'Pesanan berhasil dibayar',
            'data' => $order
        ], 200);
    }

    // Menampilkan daftar pesanan berdasarkan user_id, dengan filter opsional ?status=
    public function getByUser(Request $request, $userId)
    {
        $query = Order::with('items')->where('user_id', $userId);

        // Filter opsional berdasarkan status
        if ($request->has('status')) {
            $status = strtoupper($request->query('status'));
            
            // Validasi nilai status yang sah hanya PENDING atau PAID
            if (!in_array($status, ['PENDING', 'PAID'])) {
                return response()->json([
                    'message' => 'Data yang dikirim tidak valid',
                    'errors' => [
                        'status' => [
                            'Status harus PENDING atau PAID'
                        ]
                    ]
                ], 422);
            }

            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Berhasil mengambil daftar pesanan',
            'data' => $orders
        ], 200);
    }

    // Mengecek status kesehatan service
    public function health()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'order-service'
        ], 200);
    }
}