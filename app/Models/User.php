<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model {
    use SoftDeletes;
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'role', 'name', 'email', 'password', 'phone', 'image', 'address'
    ];

    protected $hidden = ['password'];

    public function carts() { return $this->hasMany(Cart::class, 'user_id'); }
    public function orders() { return $this->hasMany(Order::class, 'user_id'); }
    public function favorites() { return $this->hasMany(Favorite::class, 'user_id'); }
    public function sentChats() { return $this->hasMany(Chat::class, 'sender_id'); }
    public function receivedChats() { return $this->hasMany(Chat::class, 'receiver_id'); }
    public function notifications() { return $this->hasMany(Notification::class, 'user_id'); }
}
