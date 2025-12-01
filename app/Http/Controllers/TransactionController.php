<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Order;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Transaction::with(['order.user']);

        if ($user->role !== 'admin') {
            // Filter hanya transaksi milik user lewat order
            $query->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $this->validate($request, [
            'order_id' => 'required|integer|exists:orders,id',
            'payment_method' => 'required|string',
            'gross_amount' => 'required|numeric|min:0',
        ]);

        // Ambil order untuk tahu user_id pemiliknya
        $order = Order::findOrFail($validated['order_id']);
        $orderUser = $order->user; // relasi ke user pemilik order

        $existing = Transaction::where('order_id', $validated['order_id'])
        ->where('payment_status', 'pending')
        ->first();
        
        if ($existing) {
            return response()->json([
                'message' => 'Masih ada transaksi yang pending.',
                'transaction' => $existing
            ], 409);
        }

        // Buat transaksi baru
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'payment_method' => $validated['payment_method'],
            'gross_amount' => $validated['gross_amount'],
            'payment_status' => 'pending',
        ]);

        // Update status order jadi pending
        $order->update(['status' => 'pending']);

        // Kirim notifikasi ke semua admin
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'Transaksi Baru dari ' . $orderUser->name,
                'message' => 'Total: Rp' . number_format($transaction->gross_amount, 0, ',', '.') . 
                            ' | Metode: ' . ucfirst($transaction->payment_method),
                'type' => 'transaction',
                'reference_id' => $transaction->id,
            ]);
        }

        // Kirim notifikasi ke user pemilik order
        Notification::create([
            'user_id' => $orderUser->id,
            'title' => 'Transaksi Diterima',
            'message' => 'Transaksi kamu sedang menunggu konfirmasi admin.',
            'type' => 'transaction',
            'reference_id' => $transaction->id,
        ]);

        return response()->json($transaction->load('order'), 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        $order = $transaction->order;
        $orderUser = $order->user;

        $allowedStatuses = ['pending', 'paid', 'failed', 'expired'];
        if (!in_array($request->payment_status, $allowedStatuses)) {
            return response()->json(['error' => 'Status tidak valid'], 400);
        }

        $transaction->update(['payment_status' => $request->payment_status]);

        // Sinkronkan status order
        switch ($request->payment_status) {
            case 'paid':
                $order->update(['status' => 'paid']);
                break;
            case 'failed':
            case 'expired':
                $order->update(['status' => 'cancelled']);
                break;
        }

        // Notifikasi ke user pemilik order
        Notification::create([
            'user_id' => $orderUser->id,
            'title' => 'Status Pembayaran Diperbarui',
            'message' => 'Status pembayaran kamu kini: ' . ucfirst($request->payment_status),
            'type' => 'transaction',
            'reference_id' => $transaction->id,
        ]);

        // Kalau transaksi sudah paid → kirim notif ke admin
        if ($request->payment_status === 'paid') {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Pembayaran Diterima',
                    'message' => 'User ' . $orderUser->name . ' sudah membayar pesanan #' . $order->id,
                    'type' => 'transaction',
                    'reference_id' => $transaction->id,
                ]);
            }
        }

        return response()->json($transaction->load('order'));
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $transaction = Transaction::with('order.user')->findOrFail($id);

        if (!$transaction->order || !$transaction->order->user) {
            return response()->json(['error' => 'Data transaksi tidak valid'], 404);
        }


        // Cek hak akses
        if ($user->role !== 'admin' && $transaction->order->user_id !== $user->id) {
            return response()->json(['error' => 'Akses ditolak'], 403);
        }

        return response()->json($transaction);
    }
}