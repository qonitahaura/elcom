<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use App\Models\Order;
use App\Models\Transaction;

class MidtransController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    // Generate Snap (dipanggil setelah order dibuat)
    public function createSnapToken(Request $request)
    {
        $order = Order::with('user')->findOrFail($request->order_id);

        $params = [
            'transaction_details' => [
                'order_id' => $order->id,
                'gross_amount' => $order->total,
            ],
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
            ]
        ];

        $snapToken = Snap::getSnapToken($params);

        // simpan snap token ke tabel transaksi
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'payment_method' => 'midtrans',
            'payment_status' => 'pending',
            'gross_amount' => $order->total,
            'snap_token' => $snapToken
        ]);

        return response()->json([
            'snap_token' => $snapToken,
            'transaction' => $transaction
        ]);
    }

    // Callback Midtrans
    public function callback(Request $request)
{
    $serverKey = config('midtrans.server_key');

    // Ambil transaksi berdasarkan order_id
    $transaction = Transaction::where('order_id', $request->order_id)->first();

    if (!$transaction) {
        return response()->json(['message' => 'Transaction not found'], 404);
    }

    // Ambil gross amount dari DB
    $grossAmount = $transaction->gross_amount;

    // BUAT DATA DUMMY UNTUK TESTING
    $statusCode = "200";
    $transactionStatus = "pending";
    $transactionId = "TEST-" . uniqid();

    // Generate signature otomatis
    $signature = hash(
        'sha512',
        $request->order_id . $statusCode . $grossAmount . $serverKey
    );

    // Update transaksi
    $transaction->update([
        'payment_status' => $transactionStatus,
        'midtrans_order_id' => $request->order_id,
        'transaction_id' => $transactionId
    ]);

    return response()->json([
        'message' => 'Callback processed (AUTO TEST MODE)',
        'generated_signature' => $signature,
        'status_code' => $statusCode,
        'transaction_status' => $transactionStatus,
        'transaction_id' => $transactionId,
        'gross_amount' => $grossAmount
    ]);
}

}
