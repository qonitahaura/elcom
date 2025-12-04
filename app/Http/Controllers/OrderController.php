<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
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

            // Buat transaksi otomatis untuk order ini
            $transaction = Transaction::create([
                'order_id' => $order->id,
                'payment_method' => 'cod', // default, bisa ubah sesuai kebutuhan
                'gross_amount' => $total,
                'payment_status' => 'pending',
            ]);

            // Buat snap token midtrans
            \Midtrans\Config::$serverKey = config('midtrans.server_key');
            \Midtrans\Config::$isProduction = config('midtrans.is_production');
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;

            $params = [
                'transaction_details' => [
                    'order_id' => 'ORDER-' . $transaction->id . '-' . time(),
                    'gross_amount' => $transaction->gross_amount,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                ],
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);

            // simpan
            $transaction->update([
                'snap_token' => $snapToken,
                'midtrans_order_id' => $params['transaction_details']['order_id']
            ]);


            // Kirim notifikasi ke user & admin bisa di sini juga
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Transaksi Dibuat',
                'message' => 'Transaksi kamu senilai Rp' . number_format($total) . ' telah dibuat dan menunggu konfirmasi.',
                'type' => 'transaction',
                'reference_id' => $transaction->id,
            ]);

            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'Transaksi Baru Masuk',
                'message' => 'User ' . $user->name . ' membuat transaksi senilai Rp' . number_format($total),
                'type' => 'transaction',
                'reference_id' => $transaction->id,
            ]);
            }

            // Kirim notifikasi ke admin
            Notification::create([
                'user_id' => 1, // admin
                'title' => 'Pesanan Baru Masuk',
                'message' => 'User ' . $user->name . ' membuat pesanan senilai Rp' . number_format($total),
                'type' => 'order',
                'reference_id' => $order->id,
            ]);

            DB::commit();

            return response()->json([
                'order' => $order->load('items.product'),
                'transaction' => $transaction,
                'snap_token' => $snapToken
            ], 201);

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
            