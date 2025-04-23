<?php

use App\Http\Controllers\Api\Admin\Brand\BrandController;
use App\Http\Controllers\Api\Admin\Slider\SliderController;
use App\Http\Controllers\Api\Admin\Tag\TagController;
use App\Http\Controllers\Api\Admin\Category\CategoryController;
use App\Http\Controllers\Api\Admin\Product\ProductController;
use App\Http\Controllers\Api\Admin\Announcement\AnnouncementController;
use App\Http\Controllers\Api\Admin\Settings\SettingsController;
use App\Http\Controllers\Api\Admin\Product\ProductDiscountController;
use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']); // Ekleyin

Route::middleware('auth:sanctum')->prefix('admin')->group(function (){

    Route::get('/', function (){
        return 'devam';
    });

    Route::resource('category', CategoryController::class);
    Route::put('/category/{id}/change-status', [CategoryController::class, 'changeStatus']);

    Route::get('/product/filters-data', [ProductController::class, 'getFiltersData']);
    Route::resource('product', ProductController::class);
    Route::put('/product/{id}/change-status', [ProductController::class, 'changeStatus']);

    Route::resource('tag', TagController::class);

    Route::resource('brand', BrandController::class);
    Route::put('/brand/{id}/change-status', [BrandController::class, 'changeStatus']);

    Route::resource('slider', SliderController::class);
    Route::put('/slider/{slider}/change-status', [SliderController::class, 'changeStatus']);

    Route::resource('announcement', AnnouncementController::class);
    Route::put('/announcement/{announcement}/change-status', [AnnouncementController::class, 'changeStatus']);

    Route::resource('settings', SettingsController::class);

    Route::get('product-discount/search-targets', [ProductDiscountController::class, 'searchTargets']);
    Route::resource('product-discount', ProductDiscountController::class);

});

Route::get('/sliders', [\App\Http\Controllers\Api\Front\Slider\SliderController::class, 'index']);
Route::get('/products/latest', [\App\Http\Controllers\Api\Front\Product\ProductController::class, 'latest']);
Route::get('announcements/home', [\App\Http\Controllers\Api\Front\Announcement\AnnouncementController::class, 'getActiveAnnouncementsAndEvents']);
