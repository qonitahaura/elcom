<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {
    protected $fillable = [
        'order_id', 'payment_method', 'payment_status',
        'midtrans_order_id', 'transaction_id', 'gross_amount',
        'snap_token', 'proof_image'
    ];
    public function order() { return $this->belongsTo(Order::class); }

    public function user()
    {
        return $this->hasOneThrough(User::class, Order::class, 'id', 'id', 'order_id', 'user_id');
    }
}
