<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Exception;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    // Endpoint Health Check
    public function health()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'order-service'
        ], 200);
    }

    // Checkout: Membuat pesanan dari cart
    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder($request->validated()['user_id']);
            
            return response()->json([
                'message' => 'Pesanan berhasil dibuat',
                'data' => $order
            ], 201);
        } catch (Exception $e) {
            $status = $e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : 500;
            $message = $e->getMessage();
            if (($status == 500 || $status == 503) && !config('app.debug')) {
                $message = $status == 503 
                    ? "Product service atau Cart service tidak dapat dihubungi, coba lagi nanti" 
                    : "Terjadi kesalahan pada server";
            }

            return response()->json([
                'message' => $message
            ], $status);
        }
    }

    // Detail satu pesanan beserta item-itemnya
    public function show($id)
    {
        try {
            $order = $this->orderService->getOrderById($id);
            
            return response()->json([
                'message' => 'Berhasil mengambil pesanan',
                'data' => $order
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: 404);
        }
    }

    // Daftar pesanan user dengan filter opsional ?status=
    public function getByUser(Request $request, $userId)
    {
        try {
            $status = $request->query('status');
            $orders = $this->orderService->getOrdersByUser($userId, $status);

            return response()->json([
                'message' => 'Berhasil mengambil daftar pesanan',
                'data' => $orders
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Data yang dikirim tidak valid',
                'errors' => [
                    'status' => [
                        $e->getMessage()
                    ]
                ]
            ], 422);
        }
    }

    // Simulasi pembayaran (PENDING menjadi PAID)
    public function pay($id)
    {
        try {
            $order = $this->orderService->payOrder($id);

            return response()->json([
                'message' => 'Pesanan berhasil dibayar',
                'data' => $order
            ], 200);
        } catch (Exception $e) {
            $status = $e->getCode() ?: 409;
            return response()->json([
                'message' => $e->getMessage()
            ], $status);
        }
    }
}