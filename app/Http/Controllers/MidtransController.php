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

    $orderId = $request->order_id;
    $statusCode = $request->status_code;
    $grossAmount = $request->gross_amount;
    $transactionStatus = $request->transaction_status;

    // Validasi signature
    $signatureCheck = hash(
        "sha512",
        $orderId . $statusCode . $grossAmount . $serverKey
    );

    if ($signatureCheck != $request->signature_key) {
        return response()->json(['message' => 'Invalid signature'], 403);
    }

    // Cari transaksi
    $transaction = Transaction::where('order_id', $orderId)->first();

    if (!$transaction) {
        return response()->json(['message' => 'Transaction not found'], 404);
    }

    // Update sesuai status dari Midtrans
    if ($transactionStatus == "capture" || $transactionStatus == "settlement") {
        $transaction->payment_status = "paid";
    } elseif ($transactionStatus == "pending") {
        $transaction->payment_status = "pending";
    } elseif ($transactionStatus == "expire") {
        $transaction->payment_status = "expired";
    } elseif ($transactionStatus == "cancel" || $transactionStatus == "deny") {
        $transaction->payment_status = "failed";
    }

    $transaction->midtrans_order_id = $orderId;
    $transaction->transaction_id = $request->transaction_id;
    $transaction->save();

    return response()->json(['message' => 'Callback processed successfully']);
}

}
