<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::with(['items.product']);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $orders = $query->get();
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        DB::beginTransaction();
        try {
            $total = 0;

            // Buat pesanan baru
            $order = Order::create([
                'user_id' => $user->id,
                'total' => 0,
                'address' => $request->address,
                'status' => 'pending', // default
            ]);

            // Hitung total dan buat item pesanan
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $product->price * $item['quantity'];
                $total += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);
            }

            // Update total harga pesanan
            $order->update(['total' => $total]);

            // Kirim notifikasi ke admin
            Notification::create([
                'user_id' => 1, // admin
                'title' => 'Pesanan Baru Masuk',
                'message' => 'User ' . $user->name . ' membuat pesanan senilai Rp' . number_format($total),
                'type' => 'order',
                'reference_id' => $order->id,
            ]);

            DB::commit();
            return response()->json($order->load('items.product'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        // Validasi status yang diizinkan
        $allowedStatuses = ['pending', 'paid', 'cancelled', 'completed'];
        if (!in_array($request->status, $allowedStatuses)) {
            return response()->json(['error' => 'Status tidak valid'], 400);
        }

        // Update status pesanan
        $order->update(['status' => $request->status]);

        // Kirim notifikasi ke user
        Notification::create([
            'user_id' => $order->user_id,
            'title' => 'Status Pesanan Diperbarui',
            'message' => 'Pesanan kamu kini berstatus: ' . ucfirst($order->status),
            'type' => 'order',
            'reference_id' => $order->id,
        ]);

        return response()->json($order);
    }
}
            