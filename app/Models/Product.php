<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model {
    use SoftDeletes;
    protected $primaryKey = 'id';
    protected $fillable = ['image','colors','title','price','description','rating','category_id','stock'];
    public function category() { return $this->belongsTo(Category::class); }
    public function carts() { return $this->hasMany(Cart::class, 'product_id'); }
    public function orderItems() { return $this->hasMany(OrderItem::class, 'product_id'); }
    public function favorites() { return $this->hasMany(Favorite::class, 'product_id'); }
    
}
