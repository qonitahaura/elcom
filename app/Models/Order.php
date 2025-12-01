<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Order extends Model {
    protected $fillable = ['user_id', 'total', 'status', 'address'];

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(OrderItem::class, 'order_id'); }
    public function transaction() { return $this->hasOne(Transaction::class, 'order_id'); }
}
