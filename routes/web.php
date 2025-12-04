<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->post('test-callback', function(Request $request){
    Log::info('Test callback: ', $request->all());
    return response()->json(['message'=>'ok']);
});


$router->group(['prefix' => 'api'], function () use ($router) {

    // Midtrans
    $router->post('midtrans/token', 'MidtransController@createTransaction');
    $router->post('midtrans/callback', 'MidtransController@callback');


    // Auth
    $router->post('register', 'UserController@register');
    $router->post('login', 'UserController@login');

    // Produk dan Kategori (tampil publik)
    $router->get('products', 'ProductController@index');
    $router->get('products/{id}', 'ProductController@show');
    $router->get('categories', 'CategoryController@index');
    $router->get('categories/{id}', 'CategoryController@show');

    // ROUTE Auth (User atau Admin)
    $router->group(['middleware' => 'auth'], function () use ($router) {

        // Logout dan Profile
        $router->post('logout', 'UserController@logout');
        $router->get('profile', 'UserController@profile');

        // Cart
        $router->get('carts', 'CartController@index');
        $router->post('carts', 'CartController@store');
        $router->put('carts/{id}', 'CartController@update');
        $router->delete('carts/{id}', 'CartController@destroy');

        // Order
        $router->get('orders', 'OrderController@index');
        $router->post('orders', 'OrderController@store');
        $router->put('orders/{id}/status', 'OrderController@updateStatus');

        // Transaction
        $router->get('transactions', 'TransactionController@index');
        $router->get('transactions/{id}', 'TransactionController@show');
        $router->post('transactions', 'TransactionController@store');
        $router->put('transactions/{id}/status', 'TransactionController@updateStatus');

        // Favorite
        $router->get('favorites', 'FavoriteController@index');
        $router->post('favorites', 'FavoriteController@store');
        $router->delete('favorites/{id}', 'FavoriteController@destroy');

        // Chat
        $router->get('chats', 'ChatController@index');
        $router->post('chats', 'ChatController@store');

        // Notification
        $router->get('notifications', 'NotificationController@index');
        $router->put('notifications/{id}/read', 'NotificationController@markRead');
    });

    // ROUTE ADMIN (khusus role = admin)
    $router->group(['middleware' => 'admin'], function () use ($router) {
        // CRUD user
        $router->get('users', 'UserController@index');
        $router->get('users/{id}', 'UserController@show');
        $router->put('users/{id}', 'UserController@update');
        $router->delete('users/{id}', 'UserController@destroy');

        // CRUD produk & kategori (admin-only)
        $router->post('products', 'ProductController@store');
        $router->put('products/{id}', 'ProductController@update');
        $router->delete('products/{id}', 'ProductController@destroy');

        $router->post('categories', 'CategoryController@store');
        $router->put('categories/{id}', 'CategoryController@update');
        $router->delete('categories/{id}', 'CategoryController@destroy');
        
    });
});
