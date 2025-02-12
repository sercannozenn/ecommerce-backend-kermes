<?php

use App\Http\Controllers\Api\Admin\Category\CategoryController;
use App\Http\Controllers\Api\Admin\Product\ProductController;
use App\Http\Controllers\Api\Admin\Tag\TagController;
use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']); // Ekleyin

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->prefix('admin')->group(function (){

    Route::get('/', function (){
        return 'devam';
    });

    Route::resource('category', CategoryController::class);
    Route::put('/category/{id}/change-status', [CategoryController::class, 'changeStatus']);
    Route::resource('product', ProductController::class);
    Route::put('/product/{id}/change-status', [ProductController::class, 'changeStatus']);
    Route::resource('tag', TagController::class);
});
