<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model {
    use SoftDeletes;

    protected $primaryKey = 'id';

    protected $fillable = [
        'image',
        'colors',
        'title',
        'price',
        'description',
        'rating',
        'category_id',
        'stock'
    ];

    protected $casts = [
        'colors' => 'array',   // <== colors otomatis jadi array JSON
        'price' => 'float',
        'rating' => 'float',
    ];

    // Optional: Biar image jadi full URL otomatis
    protected $appends = ['image_url'];

    public function getImageUrlAttribute() {
        if (!$this->image) return null;
        return asset('storage/' . $this->image);   // contoh: http://localhost/storage/product.jpg
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function carts() { return $this->hasMany(Cart::class, 'product_id'); }
    public function orderItems() { return $this->hasMany(OrderItem::class, 'product_id'); }
    public function favorites() { return $this->hasMany(Favorite::class, 'product_id'); }
}
